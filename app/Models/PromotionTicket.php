<?php

namespace App\Models;

use App\Enums\PromotionTicketStatus;
use App\Enums\PromotionTurnStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PromotionTicket extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'status' => PromotionTicketStatus::class,
        'issued_at' => 'immutable_datetime',
        'activated_at' => 'immutable_datetime',
        'completed_at' => 'immutable_datetime',
        'cancelled_at' => 'immutable_datetime',
    ];

    public function participation(): BelongsTo
    {
        return $this->belongsTo(PromotionParticipation::class, 'participation_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(PromotionCampaign::class, 'campaign_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function turns(): HasMany
    {
        return $this->hasMany(PromotionTurn::class, 'ticket_id')->orderBy('started_at');
    }

    public function latestTurn(): HasOne
    {
        return $this->hasOne(PromotionTurn::class, 'ticket_id')->latestOfMany();
    }

    public function activeTurn(): HasOne
    {
        return $this->hasOne(PromotionTurn::class, 'ticket_id')
            ->where('status', PromotionTurnStatus::Active->value)
            ->latestOfMany();
    }

    public function results(): HasMany
    {
        return $this->hasMany(PromotionSpinResult::class, 'ticket_id')->orderBy('id');
    }

    public function latestResult(): HasOne
    {
        return $this->hasOne(PromotionSpinResult::class, 'ticket_id')->latestOfMany();
    }

    public function effectiveResult(): HasOne
    {
        return $this->hasOne(PromotionSpinResult::class, 'ticket_id')
            ->where('is_final', true)
            ->whereNull('superseded_at')
            ->latestOfMany();
    }
}
