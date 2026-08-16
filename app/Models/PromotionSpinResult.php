<?php

namespace App\Models;

use App\Enums\PromotionFulfillmentMode;
use App\Enums\PromotionMailStatus;
use App\Enums\PromotionOutcomeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromotionSpinResult extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'sequence' => 'integer',
        'outcome_type_snapshot' => PromotionOutcomeType::class,
        'fulfillment_mode_snapshot' => PromotionFulfillmentMode::class,
        'is_final' => 'boolean',
        'recorded_at' => 'immutable_datetime',
        'superseded_at' => 'immutable_datetime',
        'mail_status' => PromotionMailStatus::class,
        'mail_sent_at' => 'immutable_datetime',
        'mail_failed_at' => 'immutable_datetime',
        'mail_last_attempted_at' => 'immutable_datetime',
        'fulfilled_at' => 'immutable_datetime',
    ];

    public function turn(): BelongsTo
    {
        return $this->belongsTo(PromotionTurn::class, 'turn_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(PromotionTicket::class, 'ticket_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(PromotionCampaign::class, 'campaign_id');
    }

    public function prize(): BelongsTo
    {
        return $this->belongsTo(PromotionPrize::class, 'prize_id');
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function correctsResult(): BelongsTo
    {
        return $this->belongsTo(self::class, 'corrects_result_id');
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(self::class, 'corrects_result_id')->orderBy('sequence');
    }

    public function fulfilledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fulfilled_by');
    }
}
