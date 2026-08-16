<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionCampaignState extends Model
{
    protected $primaryKey = 'campaign_id';

    public $incrementing = false;

    protected $guarded = [];

    protected $casts = [
        'sticker_required' => 'boolean',
        'sticker_acknowledged_at' => 'immutable_datetime',
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(PromotionCampaign::class, 'campaign_id');
    }

    public function activeTurn(): BelongsTo
    {
        return $this->belongsTo(PromotionTurn::class, 'active_turn_id');
    }

    public function stickerAcknowledgedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sticker_acknowledged_by');
    }
}
