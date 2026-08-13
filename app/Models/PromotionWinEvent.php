<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionWinEvent extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'win_events';

    protected $fillable = [
        'campaign_id', 'win_id', 'participation_id', 'actor_ref', 'sequence', 'event_type', 'payload',
        'previous_hash', 'event_hash', 'occurred_at',
    ];

    protected $casts = ['payload' => 'array', 'occurred_at' => 'datetime', 'sequence' => 'integer'];

    public function campaign()
    {
        return $this->belongsTo(PromotionCampaign::class, 'campaign_id');
    }

    public function win()
    {
        return $this->belongsTo(PromotionWin::class, 'win_id');
    }

    protected static function booted(): void
    {
        static::updating(static fn (): never => throw new \LogicException('Promotion audit events are immutable.'));
        static::deleting(static fn (): never => throw new \LogicException('Promotion audit events are immutable.'));
    }
}
