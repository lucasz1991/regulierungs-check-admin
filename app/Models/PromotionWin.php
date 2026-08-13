<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionWin extends Model
{
    use HasFactory;

    public const STATUS_ISSUED = 'issued';

    public const STATUS_BOUND = 'bound';

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_FULFILLED = 'fulfilled';

    public const STATUS_DISPUTED = 'disputed';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELLED = 'cancelled';

    protected $table = 'wins';

    protected $fillable = [
        'campaign_id', 'prize_id', 'participation_id', 'token_hash', 'claim_key', 'status',
        'issued_by', 'fulfilled_by', 'prize_name_snapshot', 'fulfillment_mode_snapshot', 'expires_at', 'consumed_at', 'bound_at',
        'confirmed_at', 'disputed_at', 'fulfilled_at', 'expired_at', 'cancelled_at', 'cancellation_reason',
    ];

    protected $hidden = ['token_hash', 'claim_key'];

    protected $casts = [
        'expires_at' => 'datetime',
        'consumed_at' => 'datetime',
        'bound_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'disputed_at' => 'datetime',
        'fulfilled_at' => 'datetime',
        'expired_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function campaign()
    {
        return $this->belongsTo(PromotionCampaign::class, 'campaign_id');
    }

    public function prize()
    {
        return $this->belongsTo(PromotionPrize::class, 'prize_id');
    }

    public function participation()
    {
        return $this->belongsTo(PromotionParticipation::class, 'participation_id');
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function fulfilledBy()
    {
        return $this->belongsTo(User::class, 'fulfilled_by');
    }

    public function events()
    {
        return $this->hasMany(PromotionWinEvent::class, 'win_id')->orderBy('sequence');
    }

    public static function tokenHash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}
