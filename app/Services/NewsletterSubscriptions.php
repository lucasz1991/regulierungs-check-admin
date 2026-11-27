<?php

namespace App\Services;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class NewsletterSubscriptions
{
    public const AGREEMENT = 'Ich möchte den Regulierungs-CHECK Newsletter per E-Mail an diese Adresse erhalten.';

    private const SOURCES = ['user' => 'users', 'contact' => 'contacts'];

    public static function email(?string $email): ?string
    {
        $email = strtolower(trim((string) $email));

        return strlen($email) <= 254 && filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    public function syncSources(): void
    {
        foreach (self::SOURCES as $type => $table) {
            DB::table($table)->select($type === 'contact' ? ['id', 'email', 'mail_permission'] : ['id', 'email'])->chunkById(300, function ($rows) use ($type) {
                $known = DB::table('newsletter_subscriptions')->where('source_type', $type)
                    ->whereIn('source_id', $rows->pluck('id'))->get(['source_id', 'email', 'enabled'])->keyBy('source_id');
                foreach ($rows as $row) {
                    $sub = $known->get($row->id);
                    if (! $sub || $sub->email !== self::email($row->email) || ($sub->enabled && ($row->mail_permission ?? null) === 'blocked')) {
                        $this->forSource($type, $row->id);
                    }
                }
            });
        }
    }

    public function forSource(string $type, int $id): object
    {
        abort_unless(isset(self::SOURCES[$type]), 404);

        return DB::transaction(function () use ($type, $id) {
            $source = DB::table(self::SOURCES[$type])->where('id', $id)->lockForUpdate()->first();
            abort_unless($source, 404);
            $email = self::email($source->email);
            $key = $type.':'.$id;
            $record = $this->ensureRecord($key, $type, $id, $email);
            if ($record->email !== $email) {
                $this->event($record, 'address_changed', 'sync', null, null);
                DB::table('newsletter_subscriptions')->where('id', $record->id)->update([
                    'email' => $email, 'address_token' => (string) Str::uuid(), 'consented_at' => null,
                    'consent_source' => null, 'consent_note' => null, 'updated_at' => now(),
                    'enabled' => $record->enabled && ! DB::table('newsletter_suppressions')->where('email', $email)->exists(),
                    'version' => $record->version + 1,
                ]);
                $record = DB::table('newsletter_subscriptions')->find($record->id);
            }
            if ($email && ($source->mail_permission ?? null) === 'blocked') {
                DB::table('newsletter_suppressions')->insertOrIgnore(['email' => $email, 'created_at' => now()]);
                if ($record->enabled || $record->consented_at) {
                    DB::table('newsletter_subscriptions')->where('id', $record->id)->update([
                        'enabled' => false, 'consented_at' => null, 'consent_source' => null,
                        'consent_note' => null, 'updated_at' => now(), 'version' => $record->version + 1,
                    ]);
                    $this->event($record, 'unsubscribe', 'contact_block', null, 'Kontaktsperre übernommen.');
                    $record = DB::table('newsletter_subscriptions')->find($record->id);
                }
            }

            return $record;
        }, 3);
    }

    private function ensureRecord(string $key, string $type, ?int $id, ?string $email): object
    {
        $table = DB::table('newsletter_subscriptions');
        $row = (clone $table)->where('source_key', $key)->lockForUpdate()->first();
        if (! $row) {
            $table->insertOrIgnore(['source_key' => $key, 'source_type' => $type, 'source_id' => $id,
                'email' => $email, 'enabled' => ! DB::table('newsletter_suppressions')->where('email', $email)->exists(),
                'address_token' => (string) Str::uuid(), 'created_at' => now(), 'updated_at' => now()]);
            $row = $table->where('source_key', $key)->lockForUpdate()->first();
            abort_unless($row, 409);
        }

        return $row;
    }

    // An anonymous interest request never changes an account, proves ownership or lifts an opt-out.
    public function registerInterest(string $email): void
    {
        $email = self::email($email);
        abort_unless($email, 422);
        DB::transaction(function () use ($email) {
            foreach (self::SOURCES as $table) {
                if (DB::table($table)->whereRaw('LOWER(TRIM(email)) = ?', [$email])->exists()) {
                    return;
                }
            }
            $this->ensureRecord('public:'.hash('sha256', $email), 'public', null, $email);
        }, 3);
    }

    public function eligibleQuery(): Builder
    {
        $query = DB::table('newsletter_subscriptions as s')->select('s.*')
            ->where('s.enabled', true)->whereNotNull('s.email')->whereNotNull('s.consented_at')
            ->whereNotExists(fn ($q) => $q->selectRaw('1')->from('newsletter_suppressions as x')->whereColumn('x.email', 's.email'));

        // Check the live owner records as well as the snapshot. Transfers and deletions
        // must stop delivery even if the next background reconciliation has not run yet.
        $query->where(function ($sources) {
            $sources->where('s.source_type', 'public');
            foreach (self::SOURCES as $type => $table) {
                $sources->orWhere(function ($scope) use ($type, $table) {
                    $scope->where('s.source_type', $type)->whereExists(function ($q) use ($type, $table) {
                        $q->selectRaw('1')->from($table.' as owner')->whereColumn('owner.id', 's.source_id')
                            ->whereRaw('LOWER(TRIM(owner.email)) = s.email');
                        if ($type === 'user') {
                            $q->whereNull('owner.deleted_at')->where('owner.status', true)
                                ->whereNotNull('owner.email_verified_at');
                        } else {
                            $q->where('owner.mail_permission', '!=', 'blocked');
                        }
                    });
                });
            }
        });
        foreach (self::SOURCES as $type => $table) {
            $query->whereNotExists(function ($q) use ($type, $table) {
                $q->selectRaw('1')->from($table.' as other')->whereRaw('LOWER(TRIM(other.email)) = s.email')
                    ->where(function ($identity) use ($type) {
                        $identity->where('s.source_type', '!=', $type)->orWhereColumn('other.id', '!=', 's.source_id');
                    });
            });
        }

        return $query;
    }

    public function recipient(int $id, string $expectedEmail): ?array
    {
        $s = $this->eligibleQuery()->where('s.id', $id)->where('s.email', self::email($expectedEmail))->first();
        if (! $s) {
            return null;
        }
        $name = null;
        $mailbox = explode('@', $s->email)[0];
        if (! $s->neutral_greeting && ! preg_match('/^(info|office|kontakt|support|team|service|post|mail|vertrieb|rechnung|buchhaltung|admin|noreply|no-reply)([.+_-]|$)/i', $mailbox)) {
            if ($s->source_type === 'user') {
                $name = DB::table('customers')->where('user_id', $s->source_id)->whereNull('deleted_at')->value('first_name');
            }
            if (isset(self::SOURCES[$s->source_type]) && trim((string) $name) === '') {
                $name = DB::table(self::SOURCES[$s->source_type])->where('id', $s->source_id)->value('name');
            }
        }

        $name = trim((string) $name);

        return ['id' => $id, 'email' => $s->email, 'name' => $name && ! str_contains($name, '@') ? $name : null];
    }

    public function current(int $id): object
    {
        $row = DB::table('newsletter_subscriptions')->find($id);
        abort_unless($row, 404);
        if ($row->source_type === 'public') {
            return $row;
        }

        return $this->forSource($row->source_type, $row->source_id);
    }

    public function update(int $id, int $version, bool $enabled, bool $neutral, string $source, ?int $actor, ?string $proof = null): void
    {
        DB::transaction(function () use ($id, $version, $enabled, $neutral, $source, $actor, $proof) {
            $this->current($id);
            $row = DB::table('newsletter_subscriptions')->where('id', $id)->lockForUpdate()->first();
            abort_unless($row, 404);
            if ((int) $row->version !== $version) {
                throw ValidationException::withMessages(['newsletter' => 'Die Einstellungen wurden inzwischen geändert. Bitte lade die Ansicht neu.']);
            }
            $proof = trim((string) $proof);
            if ($enabled && $proof !== '' && DB::table('contacts')->whereRaw('LOWER(TRIM(email)) = ?', [$row->email])->where('mail_permission', 'blocked')->exists()) {
                throw ValidationException::withMessages(['newsletter' => 'Für diese Adresse besteht eine Kontaktsperre.']);
            }
            if ($enabled && ! $row->email) {
                throw ValidationException::withMessages(['newsletter' => 'Bitte hinterlege zuerst eine gültige E-Mail-Adresse.']);
            }
            if ($enabled && ! $row->enabled && $proof === '') {
                throw ValidationException::withMessages(['newsletter' => 'Zum erneuten Einschalten ist eine ausdrückliche Bestellung erforderlich.']);
            }
            if ($enabled && $proof && $row->source_type === 'user' &&
                ! DB::table('users')->where('id', $row->source_id)->whereNotNull('email_verified_at')->exists()) {
                throw ValidationException::withMessages(['newsletter' => 'Für diese Adresse fehlt die Bestätigung im Benutzerkonto.']);
            }
            $changes = ['enabled' => $enabled, 'neutral_greeting' => $neutral];
            if (! $enabled) {
                $changes += ['consented_at' => null, 'consent_source' => null, 'consent_note' => null];
                if ($row->email) {
                    DB::table('newsletter_suppressions')->insertOrIgnore(['email' => $row->email, 'created_at' => now()]);
                }
            } elseif ($proof !== '') {
                $changes += ['consented_at' => now(), 'consent_source' => $source, 'consent_note' => $proof];
                DB::table('newsletter_suppressions')->where('email', $row->email)->delete();
            }
            DB::table('newsletter_subscriptions')->where('id', $id)->update($changes + ['updated_at' => now(), 'version' => $row->version + 1]);
            $this->event($row, $enabled ? ($proof ? 'consent' : 'preference') : 'unsubscribe', $source, $actor, $proof ?: null);
        }, 3);
    }

    private function event(object $row, string $event, string $source, ?int $actor, ?string $note): void
    {
        DB::table('newsletter_preference_events')->insert(['subscription_id' => $row->id, 'email' => $row->email,
            'event' => $event, 'source' => $source, 'actor_id' => $actor, 'note' => $note, 'created_at' => now()]);
    }
}
