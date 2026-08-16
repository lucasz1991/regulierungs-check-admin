<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class PromotionWinEvent extends Model
{
    public $timestamps = false;

    protected $table = 'win_events';

    protected $guarded = [];

    protected $casts = ['payload' => 'array', 'sequence' => 'integer', 'occurred_at' => 'immutable_datetime'];

    protected static function booted(): void
    {
        static::updating(static fn (): never => throw new LogicException('Promotion audit events are immutable.'));
        static::deleting(static fn (): never => throw new LogicException('Promotion audit events are immutable.'));
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(PromotionCampaign::class, 'campaign_id');
    }

    public function win(): BelongsTo
    {
        return $this->belongsTo(PromotionWin::class, 'win_id');
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(PromotionTicket::class, 'ticket_id');
    }

    public function turn(): BelongsTo
    {
        return $this->belongsTo(PromotionTurn::class, 'turn_id');
    }

    public function spinResult(): BelongsTo
    {
        return $this->belongsTo(PromotionSpinResult::class, 'spin_result_id');
    }

}
