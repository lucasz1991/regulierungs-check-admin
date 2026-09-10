<?php

namespace App\Models;

use App\Enums\PromotionGiftCodeStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionGiftCode extends Model
{
    protected $guarded = ['id', 'code_encrypted', 'code_fingerprint'];

    protected $hidden = ['code_encrypted', 'code_fingerprint'];

    protected $casts = [
        'status' => PromotionGiftCodeStatus::class,
        'reserved_at' => 'immutable_datetime',
        'sent_at' => 'immutable_datetime',
        'disabled_at' => 'immutable_datetime',
    ];

    public function campaign(): BelongsTo { return $this->belongsTo(PromotionCampaign::class, 'campaign_id'); }
    public function prize(): BelongsTo { return $this->belongsTo(PromotionPrize::class, 'prize_id'); }
    public function result(): BelongsTo { return $this->belongsTo(PromotionSpinResult::class, 'spin_result_id'); }
    public function uploadedBy(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
    public function disabledBy(): BelongsTo { return $this->belongsTo(User::class, 'disabled_by'); }
}
