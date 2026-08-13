<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionParticipation extends Model
{
    use HasFactory;

    protected $table = 'participations';

    protected $fillable = ['campaign_id', 'user_id', 'public_id'];

    protected $appends = ['status', 'confirmed_at', 'fulfilled_at'];

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function campaign()
    {
        return $this->belongsTo(PromotionCampaign::class, 'campaign_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wins()
    {
        return $this->hasMany(PromotionWin::class, 'participation_id');
    }

    public function currentWin()
    {
        return $this->hasOne(PromotionWin::class, 'participation_id')->latestOfMany();
    }

    public function win()
    {
        return $this->currentWin();
    }

    public function getStatusAttribute(): ?string
    {
        return $this->currentWin?->status;
    }

    public function getConfirmedAtAttribute()
    {
        return $this->currentWin?->confirmed_at;
    }

    public function getFulfilledAtAttribute()
    {
        return $this->currentWin?->fulfilled_at;
    }
}
