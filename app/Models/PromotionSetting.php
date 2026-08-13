<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PromotionSetting extends Model
{
    protected $table = 'promotion_settings';

    protected $guarded = [];

    protected $hidden = [
        'audit_secret_encrypted',
        'configuration_mac',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'qr_ttl_minutes' => 'integer',
    ];
}
