<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SocialAuthProviderSetting extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['client_secret_encrypted', 'configuration_mac'];

    protected $casts = [
        'enabled' => 'boolean',
        'client_secret_expires_at' => 'immutable_datetime',
    ];
}
