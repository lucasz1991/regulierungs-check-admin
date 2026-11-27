<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MediaController;
use App\Models\Mail;
use App\Models\NewsletterCampaign;
use App\Models\Post;
use App\Models\Setting;
use App\Services\NewsletterMailPreview;
use App\Services\NewsletterSubscriptions;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class NewsletterController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->validate(['status' => ['nullable', Rule::in(['draft', 'scheduled', 'sending', 'sent', 'blocked'])]])['status'] ?? null;

        return view('admin.newsletter.index', [
            'campaigns' => NewsletterCampaign::query()->when($status, fn ($q) => $q->where('status', $status))->latest('id')->paginate(20)->withQueryString(),
            'status' => $status,
            'articles' => Gate::allows('content.news.manage') ? Post::where('type', 'news')->orderByDesc('id')->get(['id', 'title']) : collect(),
        ]);
    }

    public function create(Request $request)
    {
        $source = $request->validate(['post_id' => 'nullable|integer|min:1'])['post_id'] ?? null;
        if ($source) {
            Gate::authorize('content.news.manage');
            $campaign = DB::transaction(function () use ($source) {
                $post = Post::where('type', 'news')->lockForUpdate()->findOrFail($source);
                $key = 'news:'.$post->id;

                return NewsletterCampaign::query()->firstOrCreate(['draft_key' => $key], NewsletterCampaign::newsValues($post));
            });
        } else {
            $campaign = NewsletterCampaign::create(['name' => 'Neue Ausgabe', 'subject' => '', 'body' => '']);
        }

        return to_route('admin.newsletter.show', $campaign);
    }

    public function show(NewsletterCampaign $campaign)
    {
        return view('admin.newsletter.show', [
            'campaign' => $campaign,
            'report' => DB::table('newsletter_deliveries')->where('campaign_id', $campaign->id)
                ->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'exceptions' => $campaign->status === 'draft' ? null : DB::table('newsletter_deliveries')
                ->where('campaign_id', $campaign->id)->whereIn('status', ['failed', 'uncertain', 'skipped'])
                ->orderBy('id')->paginate(20, ['email', 'status', 'updated_at']),
        ]);
    }

    private function checkVersion(Request $request, NewsletterCampaign $campaign, string $status = 'draft'): void
    {
        $revision = $request->validate(['revision' => 'required|integer|min:1'])['revision'];
        if ($campaign->status !== $status || (int) $revision !== $campaign->revision) {
            throw ValidationException::withMessages(['revision' => 'Die Ausgabe wurde inzwischen geändert. Bitte lade den aktuellen Stand.']);
        }
    }

    public function save(Request $request, NewsletterCampaign $campaign, MediaController $media)
    {
        $this->checkVersion($request, $campaign);
        $fields = $request->validate([
            'name' => 'required|string|max:255', 'subject' => 'nullable|string|max:255', 'body' => 'nullable|string|max:50000',
            'preheader' => 'nullable|string|max:255',
            'button_label' => 'nullable|string|max:80', 'link' => 'nullable|url:http,https|max:2048',
            'greeting' => 'nullable|string|max:255', 'audience' => ['required', Rule::in(['all', 'users', 'contacts'])],
            'focus_x' => 'nullable|integer|between:0,100', 'focus_y' => 'nullable|integer|between:0,100',
            'image_alt' => 'nullable|string|max:500', 'include_image' => 'nullable|boolean',
            'after' => ['nullable', Rule::in(['edit', 'preview'])],
        ]);
        $preview = ($fields['after'] ?? '') === 'preview';
        unset($fields['after']);
        $fields['subject'] = trim((string) ($fields['subject'] ?? ''));
        $fields['body'] = trim((string) ($fields['body'] ?? ''));
        $fields['include_image'] = $campaign->post_id && $request->boolean('include_image');
        $fields['focus_x'] = (int) ($fields['focus_x'] ?? $campaign->focus_x);
        $fields['focus_y'] = (int) ($fields['focus_y'] ?? $campaign->focus_y);
        if ($campaign->post_id) {
            $fields['link'] = NewsletterCampaign::publicUrl('/news/lesen/'.$campaign->post_id);
        }
        $campaign->fill($fields);
        if ($campaign->include_image && (! $campaign->image_url || $campaign->isDirty(['focus_x', 'focus_y']))) {
            if (! $campaign->image_source) {
                throw ValidationException::withMessages(['include_image' => 'Im Beitrag ist kein Hauptbild hinterlegt.']);
            }
            try {
                $image = $media->newsletterImage($campaign->post_id, $campaign->focus_x, $campaign->focus_y, $campaign->image_source);
            } catch (\RuntimeException $e) {
                throw ValidationException::withMessages(['include_image' => $e->getMessage()]);
            }
            $campaign->fill(['image_url' => $image['url'], 'image_width' => $image['width'], 'image_height' => $image['height']]);
        } elseif (! $campaign->include_image) {
            $campaign->fill(['image_url' => null, 'image_width' => null, 'image_height' => null]);
        }
        $changes = $campaign->getDirty();
        DB::transaction(function () use ($campaign, $changes, $request) {
            $current = NewsletterCampaign::lockForUpdate()->findOrFail($campaign->id);
            $this->checkVersion($request, $current);
            $current->fill($changes);
            $current->revision++;
            $current->save();
        });

        return to_route($preview ? 'admin.newsletter.preview' : 'admin.newsletter.show', $campaign)->with('notice', 'Entwurf gespeichert.');
    }

    public function source(Request $request, NewsletterCampaign $campaign)
    {
        Gate::authorize('content.news.manage');
        $action = $request->validate(['action' => ['required', Rule::in(['refresh', 'detach'])]])['action'];
        DB::transaction(function () use ($request, $campaign, $action) {
            $current = NewsletterCampaign::lockForUpdate()->findOrFail($campaign->id);
            $this->checkVersion($request, $current);
            abort_unless($current->post_id, 404);
            if ($action === 'refresh') {
                $current->fill(NewsletterCampaign::newsValues(Post::where('type', 'news')->findOrFail($current->post_id)));
            } else {
                $current->fill(['draft_key' => null, 'post_id' => null, 'article_title' => null, 'link' => null, 'include_image' => false,
                    'image_source' => null, 'image_url' => null, 'image_width' => null, 'image_height' => null, 'image_alt' => null]);
            }
            $current->revision++;
            $current->save();
        });

        return to_route('admin.newsletter.show', $campaign)->with('notice', $action === 'refresh' ? 'Gespeicherte News-Inhalte übernommen.' : 'Die Ausgabe ist jetzt ein freier Newsletter.');
    }

    private function greeting(Request $request): string
    {
        return match ($request->input('profile')) {
            'contact' => 'Hallo Kim Sommer,', 'neutral' => 'Hallo,', default => 'Hallo Alex,',
        };
    }

    private function audience(Builder $query, NewsletterCampaign $campaign): Builder
    {
        if ($campaign->audience !== 'all') {
            $query->where('s.source_type', $campaign->audience === 'users' ? 'user' : 'contact');
        }

        return $query;
    }

    private function recipientCounts(NewsletterCampaign $campaign, NewsletterSubscriptions $subscriptions)
    {
        // Only the central eligibility query decides who can receive mail.
        $records = $this->audience(DB::table('newsletter_subscriptions as s'), $campaign)
            ->leftJoinSub($subscriptions->eligibleQuery()->select('s.id'), 'eligible', 'eligible.id', '=', 's.id')
            ->leftJoin('newsletter_suppressions as suppression', 'suppression.email', '=', 's.email')
            ->select('s.email')->selectRaw("CASE
                WHEN eligible.id IS NOT NULL THEN 'eligible'
                WHEN suppression.email IS NOT NULL OR s.enabled = 0 THEN 'disabled'
                WHEN s.email IS NULL THEN 'address'
                WHEN s.consented_at IS NULL THEN 'proof'
                ELSE 'identity'
            END AS reason");

        return DB::query()->fromSub($records, 'audience')
            ->select('reason')->selectRaw("CASE WHEN reason = 'eligible' THEN COUNT(DISTINCT email) ELSE COUNT(*) END AS total")
            ->groupBy('reason')->pluck('total', 'reason');
    }

    public function preview(Request $request, NewsletterCampaign $campaign, NewsletterMailPreview $renderer, NewsletterSubscriptions $subscriptions)
    {
        $content = $campaign->content($this->greeting($request));

        return view('admin.newsletter.preview', [
            'campaign' => $campaign, 'html' => $renderer->render($content), 'text' => $renderer->renderText($content),
            'profile' => $request->input('profile', 'user'),
            'counts' => $campaign->status === 'draft' && Gate::allows('newsletter.send') ? $this->recipientCounts($campaign, $subscriptions) : collect(),
        ]);
    }

    private function checkReady(NewsletterCampaign $campaign): void
    {
        if (trim($campaign->subject) === '' || trim($campaign->body) === '') {
            throw ValidationException::withMessages(['subject' => 'Vor der Freigabe bitte Betreff und Nachricht ergänzen.']);
        }
        if ($campaign->include_image && (! $campaign->image_url || $campaign->image_width < 2 || $campaign->image_width !== 2 * (int) $campaign->image_height)) {
            throw ValidationException::withMessages(['image' => 'Bitte den Bildausschnitt speichern oder die Ausgabe ohne Bild vorbereiten.']);
        }
    }

    public function test(Request $request, NewsletterCampaign $campaign)
    {
        $this->checkVersion($request, $campaign);
        $this->checkReady($campaign);
        $fields = $request->validate(['test_email' => 'required|email|max:254', 'test_key' => 'required|uuid']);
        $content = $campaign->content($this->greeting($request));
        $content['from_user_id'] = $request->user()->getKey();
        DB::transaction(fn () => Mail::firstOrCreate(['dispatch_key' => 'newsletter-test-'.$campaign->id.'-'.$fields['test_key']], [
            'type' => 'mail', 'content' => $content, 'status' => false,
            'recipients' => [['email' => $fields['test_email'], 'status' => false, 'state' => 'pending']],
        ]));

        return to_route('admin.newsletter.preview', $campaign)->with('notice', 'Testmail für '.$fields['test_email'].' in die Versandwarteschlange gelegt.');
    }

    private function visibleAt(NewsletterCampaign $campaign, CarbonImmutable $at): void
    {
        if ($campaign->post_id && (! Setting::enabled('webcontent', 'news_enabled') ||
            ! Post::where('type', 'news')->where('published', true)->where('published_at', '<=', $at->setTimezone('Europe/Berlin'))->whereKey($campaign->post_id)->exists())) {
            throw ValidationException::withMessages(['source' => 'Der verknüpfte Artikel muss zum Versand öffentlich erreichbar sein.']);
        }
    }

    public function schedule(Request $request, NewsletterCampaign $campaign, NewsletterSubscriptions $subscriptions)
    {
        $fields = $request->validate(['scheduled' => 'nullable|date_format:Y-m-d\TH:i']);
        $when = empty($fields['scheduled']) ? CarbonImmutable::now('UTC') : CarbonImmutable::createFromFormat('!Y-m-d\TH:i', $fields['scheduled'], 'Europe/Berlin')->utc();
        if (! empty($fields['scheduled']) && ($when->isPast() || $when->setTimezone('Europe/Berlin')->format('Y-m-d\TH:i') !== $fields['scheduled'])) {
            throw ValidationException::withMessages(['scheduled' => 'Bitte eine gültige zukünftige Berliner Ortszeit wählen.']);
        }
        $this->checkVersion($request, $campaign);
        $subscriptions->syncSources();
        DB::transaction(function () use ($campaign, $request, $subscriptions, $when) {
            $current = NewsletterCampaign::lockForUpdate()->findOrFail($campaign->id);
            $this->checkVersion($request, $current);
            $this->checkReady($current);
            $this->visibleAt($current, $when);
            $recipients = $this->audience($subscriptions->eligibleQuery(), $current);
            $count = 0;
            $recipients->chunkById(200, function ($group) use ($current, &$count) {
                $now = now();
                $rows = $group->map(fn ($s) => [
                    'campaign_id' => $current->id, 'subscription_id' => $s->id, 'email' => mb_strtolower(trim($s->email)),
                    'user_id' => $s->source_type === 'user' ? $s->source_id : null,
                    'contact_id' => $s->source_type === 'contact' ? $s->source_id : null,
                    'status' => 'pending', 'created_at' => $now, 'updated_at' => $now,
                ])->all();
                $count += DB::table('newsletter_deliveries')->insertOrIgnore($rows);
            }, 's.id', 'id');
            if (! $count) {
                throw ValidationException::withMessages(['audience' => 'In dieser Zielgruppe gibt es keine versandberechtigte Adresse.']);
            }
            $current->update(['status' => 'scheduled', 'scheduled_at' => $when, 'revision' => $current->revision + 1, 'draft_key' => null]);
        });

        return to_route('admin.newsletter.show', $campaign)->with('notice', 'Ausgabe und Empfängerliste sind für den Versand freigegeben.');
    }

    public function changeSchedule(Request $request, NewsletterCampaign $campaign)
    {
        $action = $request->validate(['action' => ['required', Rule::in(['withdraw', 'resume', 'retry'])]])['action'];
        DB::transaction(function () use ($request, $campaign, $action) {
            $current = NewsletterCampaign::lockForUpdate()->findOrFail($campaign->id);
            $this->checkVersion($request, $current, ['withdraw' => 'scheduled', 'resume' => 'blocked', 'retry' => 'sent'][$action]);
            $deliveries = DB::table('newsletter_deliveries')->where('campaign_id', $current->id);
            if ($action === 'withdraw') {
                if ((clone $deliveries)->where('status', '!=', 'pending')->exists()) {
                    throw ValidationException::withMessages(['status' => 'Der Versand hat bereits begonnen.']);
                }
                $deliveries->delete();
                $current->fill(['status' => 'draft', 'scheduled_at' => null]);
                if ($current->post_id && Post::where('type', 'news')->lockForUpdate()->find($current->post_id)) {
                    $draftKey = 'news:'.$current->post_id;
                    if (! NewsletterCampaign::where('draft_key', $draftKey)->exists()) {
                        $current->draft_key = $draftKey;
                    }
                }
            } else {
                $this->visibleAt($current, CarbonImmutable::now('UTC'));
                if ($action === 'retry' && ! $deliveries->where('status', 'failed')->update(['status' => 'pending', 'queued_at' => null, 'updated_at' => now()])) {
                    throw ValidationException::withMessages(['status' => 'Keine eindeutig fehlgeschlagene Übergabe gefunden.']);
                }
                if ($action === 'resume') {
                    $deliveries->where('status', 'pending')->update(['queued_at' => null, 'updated_at' => now()]);
                }
                $current->fill(['status' => 'scheduled', 'scheduled_at' => CarbonImmutable::now('UTC')]);
            }
            $current->revision++;
            $current->save();
        });

        return to_route('admin.newsletter.show', $campaign)->with('notice', 'Versandplanung aktualisiert.');
    }
}
