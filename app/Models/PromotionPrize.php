<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionPrize extends Model
{
    use HasFactory;

    public const FULFILLMENT_ONSITE = 'onsite_staff';

    public const FULFILLMENT_EXTERNAL = 'external_admin';

    protected $table = 'prizes';

    protected $fillable = [
        'campaign_id', 'code', 'name', 'fulfillment_mode', 'quota',
        'reserved_count', 'is_active', 'sort_order', 'configuration',
    ];

    protected $casts = [
        'quota' => 'integer',
        'reserved_count' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'configuration' => 'array',
    ];

    public function campaign()
    {
        return $this->belongsTo(PromotionCampaign::class, 'campaign_id');
    }

    public function wins()
    {
        return $this->hasMany(PromotionWin::class, 'prize_id');
    }

    public function hasQuota(): bool
    {
        return $this->is_active && $this->reserved_count < $this->quota;
    }
}
