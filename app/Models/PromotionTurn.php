<?php

namespace App\Models;

use App\Enums\PromotionTurnStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PromotionTurn extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => PromotionTurnStatus::class,
        'started_at' => 'immutable_datetime',
        'completed_at' => 'immutable_datetime',
        'released_at' => 'immutable_datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(PromotionTicket::class, 'ticket_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(PromotionCampaign::class, 'campaign_id');
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function releasedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'released_by');
    }

    public function results(): HasMany
    {
        return $this->hasMany(PromotionSpinResult::class, 'turn_id')->orderBy('sequence');
    }

    public function latestResult(): HasOne
    {
        return $this->hasOne(PromotionSpinResult::class, 'turn_id')->latestOfMany();
    }

    public function effectiveResult(): HasOne
    {
        return $this->hasOne(PromotionSpinResult::class, 'turn_id')
            ->where('is_final', true)
            ->whereNull('superseded_at')
            ->latestOfMany();
    }
}
