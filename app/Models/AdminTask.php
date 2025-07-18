<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AdminTask extends Model
{
    use HasFactory;

    /**
     * Die Felder, die massenweise zuweisbar sind.
     */
    protected $fillable = [
        'title',
        'description',
        'status',
        'type',
        'priority',
        'related_model_type',
        'related_model_id',
        'assigned_to_user_id',
        'completed_at',
    ];

    /**
     * Casts für spezielle Felder.
     */
    protected $casts = [
        'completed_at' => 'datetime',
    ];

    /**
     * Enum-ähnliche Konstanten für Status.
     */
    public const STATUS_OPEN = 0;
    public const STATUS_IN_PROGRESS = 1;
    public const STATUS_DONE = 2;

    /**
     * Enum-ähnliche Konstanten für Priorität.
     */
    public const PRIORITY_LOW = 0;
    public const PRIORITY_NORMAL = 1;
    public const PRIORITY_HIGH = 2;

    /**
     * Polymorphe Beziehung zum verbundenen Modell (z. B. ClaimRating, User etc.).
     */
    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Beziehung zum zugewiesenen Benutzer.
     */
    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    /**
     * Hilfsmethode: Ist die Aufgabe erledigt?
     */
    public function isCompleted(): bool
    {
        return !is_null($this->completed_at) && $this->status === self::STATUS_DONE;
    }

    /**
     * Hilfsmethode: Aufgabe abschließen.
     */
    public function markAsCompleted(): void
    {
        $this->completed_at = Carbon::now();
        $this->status = self::STATUS_DONE;
        $this->save();
    }
}
