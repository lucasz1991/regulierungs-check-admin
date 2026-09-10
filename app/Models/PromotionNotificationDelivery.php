<?php

namespace App\Models;

use App\Enums\PromotionNotificationStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionNotificationDelivery extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'status' => PromotionNotificationStatus::class,
        'sent_at' => 'immutable_datetime',
        'failed_at' => 'immutable_datetime',
        'last_attempted_at' => 'immutable_datetime',
    ];

    public function result(): BelongsTo { return $this->belongsTo(PromotionSpinResult::class, 'spin_result_id'); }
}
