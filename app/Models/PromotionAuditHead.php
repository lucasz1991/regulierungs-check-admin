<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromotionAuditHead extends Model
{
    use HasFactory;

    protected $table = 'promotion_audit_heads';

    protected $primaryKey = 'campaign_id';

    public $incrementing = false;

    protected $fillable = [
        'campaign_id', 'last_sequence', 'last_hash',
    ];

    protected $casts = [
        'last_sequence' => 'integer',
    ];

    public function campaign()
    {
        return $this->belongsTo(PromotionCampaign::class, 'campaign_id');
    }
}
