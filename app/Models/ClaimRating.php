<?php

namespace App\Models;

use App\Http\Controllers\Customer\ClaimRatingController;
use App\Jobs\ClaimRatingAIEval;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\AdminTask;

class ClaimRating extends Model
{
    use HasFactory, SoftDeletes;

    // --------------------------------------
    // Status-Konstanten
    // --------------------------------------
    public const STATUS_PENDING            = 'pending';
    public const STATUS_RATED              = 'rated';
    public const STATUS_APPROVED           = 'approved';
    public const STATUS_REJECTED           = 'rejected';
    public const STATUS_PUBLISHED          = 'published';
    public const STATUS_PENDING_VALIDATION = 'pending_validation';

    protected $fillable = [
        'user_id',
        'insurance_subtype_id',
        'insurance_type_id',
        'insurance_id',
        'rating_questionnaire_versions_id',
        'answers',
        'status',
        'attachments',
        'rating_score',
        'tag_ids',
        'moderator_comment',
        'is_public',
        'admin_review',
        'data',
        'verification_hash',
    ];

    protected $casts = [
        'answers'       => 'array',
        'attachments'   => 'array',
        'is_public'     => 'boolean',
        'tag_ids'       => 'array',
        'admin_review'  => 'array',
        'data'          => 'array',
    ];

    protected $appends = [
        'verification_score',
    ];

    // --------------------------------------
    // Boot-Callbacks
    // --------------------------------------
    protected static function boot()
    {
        parent::boot();

        static::created(function (ClaimRating $claimRating) {
            ClaimRatingController::evaluateScore($claimRating);
        });

        static::updated(function (ClaimRating $claimRating) {
            // AI-Re-Score nur, wenn Antworten/Attachments geändert wurden
            if ($claimRating->wasChanged('attachments') || $claimRating->wasChanged('answers')) {
                ClaimRatingController::evaluateScore($claimRating);
            }

            // AdminTask nur, wenn sich data geändert hat (inkl. verification)
            if ($claimRating->wasChanged('data')) {
                $claimRating->handleDataChangedAdminTasks();
            }
        });
    }

    // --------------------------------------
    // Verifikations-Logik (data['verification'])
    // --------------------------------------

    /**
     * Zugriff auf data['verification'] mit Defaults.
     *
     * [
     *   'state'            => 'none|pending|approved|rejected',
     *   'caseNumber'       => string|null,
     *   'casefileUploaded' => bool,
     * ]
     */
    public function getVerificationAttribute(): array
    {
        $data = $this->data ?? [];

        return array_merge([
            'state'            => 'none',
            'caseNumber'       => null,
            'casefileUploaded' => false,
        ], $data['verification'] ?? []);
    }

    /**
     * Teil-Update der Verification-Struktur in data.
     */
    public function setVerification(array $payload): void
    {
        $data = $this->data ?? [];

        $data['verification'] = array_merge($this->verification, $payload);

        $this->data = $data;
    }

    /**
     * Ab der zweiten veröffentlichten Bewertung eines Users ist eine Verifikation nötig.
     */
    public function requiresVerification(): bool
    {
        // Bereits öffentlich → keine zusätzliche Verifikation mehr nötig
        if ($this->is_public) {
            return false;
        }

        $publishedCount = static::where('user_id', $this->user_id)
            ->where('is_public', true)
            ->count();

        return $publishedCount >= 1;
    }

    /**
     * Hat diese Bewertung überhaupt schon einen Verifikationszustand != 'none'?
     */
    public function hasVerification(): bool
    {
        return $this->verification['state'] !== 'none'
            || ! empty($this->verification['caseNumber'])
            || $this->verification['casefileUploaded'] === true;
    }

    /**
     * Gibt es Verifikationsdateien (Typ: claim_verification)?
     */
    public function hasVerificationFiles(): bool
    {
        return $this->verificationFiles()->exists();
    }

    /**
     * Bewertung in den Verifikationsstatus setzen.
     */
    public function markVerificationPending(string $caseNumber): void
    {
        // mindestens eine Verifikationsdatei vorhanden?
        $hasFiles = $this->hasVerificationFiles();

        $this->setVerification([
            'state'            => 'pending',
            'caseNumber'       => $caseNumber,
            'casefileUploaded' => $hasFiles,
        ]);

        $this->status    = self::STATUS_PENDING_VALIDATION;
        $this->is_public = false;

        $this->save();
    }

    /**
     * Optional für Admin-Workflow: Verifikation genehmigen.
     */
    public function markVerificationApproved(): void
    {
        $this->setVerification([
            'state' => 'approved',
        ]);

        $this->status    = self::STATUS_PUBLISHED;
        $this->is_public = true;

        $this->save();
    }

    /**
     * Optional für Admin-Workflow: Verifikation ablehnen.
     */
    public function markVerificationRejected(string $reason = null): void
    {
        $this->setVerification([
            'state' => 'rejected',
        ]);

        $this->status    = self::STATUS_REJECTED;
        $this->is_public = false;

        if ($reason) {
            $adminReview = $this->admin_review ?? [];
            $adminReview['verification_reject_reason'] = $reason;
            $this->admin_review = $adminReview;
        }

        $this->save();
    }

    // --------------------------------------
    // AI-Analyse / Scorings
    // --------------------------------------

    public function reanalyze(): void
    {
        $this->is_public = false;
        $this->status    = self::STATUS_PENDING;
        $this->saveQuietly();

        ClaimRatingAIEval::dispatch($this);
    }

    public function comment(): string
    {
        return $this->attachments['scorings']['ai_overall_comment'] ?? '';
    }

    public function score()
    {
        return $this->rating_score ?? '';
    }

    /**
     * Dauer der Regulierung in Tagen (aus answers['selectedDates']).
     */
    public function ratingDuration(): ?int
    {
        $start = isset($this->answers['selectedDates']['started_at'])
            ? Carbon::parse($this->answers['selectedDates']['started_at'])
            : null;

        if (! $start) {
            return null;
        }

        $end = isset($this->answers['is_closed']) && $this->answers['is_closed']
            ? Carbon::parse($this->answers['selectedDates']['ended_at'] ?? now())
            : now();

        return $start->diffInDays($end);
    }

    // --------------------------------------
    // Beziehungen
    // --------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function insurance(): BelongsTo
    {
        return $this->belongsTo(Insurance::class);
    }

    public function insuranceType(): BelongsTo
    {
        return $this->belongsTo(InsuranceType::class);
    }

    public function insuranceSubtype(): BelongsTo
    {
        return $this->belongsTo(InsuranceSubtype::class);
    }

    public function questionnaireVersion(): BelongsTo
    {
        return $this->belongsTo(
            RatingQuestionnaireVersion::class,
            'rating_questionnaire_versions_id'
        );
    }

    /**
     * Alle Tags (RatingTag) passend zu tag_ids.
     */
    public function tags()
    {
        if (! $this->tag_ids || ! is_array($this->tag_ids)) {
            return collect();
        }

        return RatingTag::whereIn('id', $this->tag_ids)->get();
    }

    /**
     * Alle Files, die an diese ClaimRating hängen (morphMany).
     */
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    /**
     * Nur Verifikationsdateien (Mehrzahl!) – mehrere Files möglich.
     */
    public function verificationFiles(): MorphMany
    {
        return $this->files()->where('type', 'claim_verification');
    }

    /**
     * AdminTasks, die sich auf diese ClaimRating beziehen.
     */
    public function adminTasks(): MorphMany
    {
        return $this->morphMany(AdminTask::class, 'related');
    }

    /**
     * Gibt zurück, ob diese Bewertung veröffentlicht werden darf.
     */
    public function canBePublished(): bool
    {
        // 1. Wenn keine Verifikation nötig ist → sofort publishbar
        if (! $this->requiresVerification()) {
            return true;
        }

        // 2. Verifikation nötig → prüfen, ob alles erfüllt ist
        $v = $this->verification;

        // Muss genehmigt sein
        if ($v['state'] !== 'approved') {
            return false;
        }

        // Fallnummer muss vorhanden sein
        if (empty($v['caseNumber'])) {
            return false;
        }

        // Mindestens eine Datei muss vorhanden sein
        if (! $this->hasVerificationFiles()) {
            return false;
        }

        return true;
    }

    // --------------------------------------
    // AdminTask-Logik bei Datenänderung
    // --------------------------------------

    /**
     * Wird aus dem updated-Callback aufgerufen, wenn sich data geändert hat.
     * Prüft, ob sich speziell der Verification-Status geändert hat und
     * legt ggf. einen AdminTask an.
     */
    protected function handleDataChangedAdminTasks(): void
    {
        // ursprüngliche data (vor dem Update)
        $before = $this->getOriginal('data');
        if (is_string($before)) {
            $before = json_decode($before, true) ?? [];
        } elseif (! is_array($before)) {
            $before = [];
        }

        // aktuelle data (nach dem Update, dank Cast bereits Array)
        $after = $this->data ?? [];

        $beforeVerification = $before['verification'] ?? [];
        $afterVerification  = $after['verification'] ?? [];

        $beforeState = $beforeVerification['state'] ?? 'none';
        $afterState  = $afterVerification['state'] ?? 'none';

        // Nur wenn der Status von etwas anderem auf 'pending' gewechselt ist
        if ($beforeState !== 'pending' && $afterState === 'pending') {
            $this->createVerificationAdminTask();
        }
    }

    /**
     * Legt (falls noch nicht vorhanden) eine AdminTask für diese ClaimRating-Verifikation an.
     */
    protected function createVerificationAdminTask(): AdminTask
    {
        $caseNumber = $this->verification['caseNumber'] ?? null;

        $title = 'Fall-Verifikation prüfen';
        $descriptionLines = [
            'Bitte die Fall-Verifikation für die Schadenbewertung prüfen.',
            '',
            'Bewertungs-ID: ' . $this->id,
            'User-ID: ' . $this->user_id,
        ];

        if ($this->insurance) {
            $descriptionLines[] = 'Versicherung: ' . $this->insurance->name;
        }

        if ($caseNumber) {
            $descriptionLines[] = 'Fallnummer: ' . $caseNumber;
        }

        $description = implode("\n", $descriptionLines);

        // firstOrCreate → verhindert Dubletten für dieselbe ClaimRating
        return AdminTask::firstOrCreate(
            [
                'type'              => 'claim_verification',
                'related_model_type'=> self::class,
                'related_model_id'  => $this->id,
            ],
            [
                'title'       => $title,
                'description' => $description,
                'status'      => AdminTask::STATUS_OPEN,
                'priority'    => AdminTask::PRIORITY_HIGH,
            ]
        );
    }

        /**
     * Verifikations-Score für den Kreis-Indikator.
     *
     * - 0   = keine Verifikation / nicht verifizierte E-Mail
     * - 20  = User-E-Mail verifiziert
     * - 60  = E-Mail verifiziert + Claim-Verifikation approved
     */
    public function getVerificationScoreAttribute(): int
    {
        $score = 0;

        // 1) Verifizierte E-Mail des Users → +20
        if ($this->user && $this->user->email_verified_at) {
            $score += 20;
        }

        // 2) Claim-spezifische Verifikation approved → +40
        $v = $this->verification; // kommt aus getVerificationAttribute()

        if (($v['state'] ?? null) === 'approved') {
            $score += 40;
        }

        return $score;
    }

}
