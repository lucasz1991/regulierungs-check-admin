<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionCampaign extends Model
{
    use HasFactory;

    protected $table = 'campaigns';

    protected $fillable = ['name', 'code', 'starts_at', 'ends_at', 'is_active', 'created_by'];

    protected $casts = [
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function prizes()
    {
        return $this->hasMany(PromotionPrize::class, 'campaign_id');
    }

    public function participations()
    {
        return $this->hasMany(PromotionParticipation::class, 'campaign_id');
    }

    public function wins()
    {
        return $this->hasMany(PromotionWin::class, 'campaign_id');
    }

    public function isOpen(): bool
    {
        $now = now();

        return $this->is_active
            && ($this->starts_at === null || $this->starts_at->lte($now))
            && ($this->ends_at === null || $this->ends_at->gte($now));
    }

    public function isCurrentlyActive(): bool
    {
        return $this->isOpen();
    }
}
