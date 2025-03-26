<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'description', 'discount_type', 'discount_value',
        'min_order_value', 'max_discount_value', 'start_date', 'end_date',
        'usage_limit', 'user_specific', 'applies_to', 'status',
    ];

    protected $casts = [
        'applies_to' => 'array',
    ];

    // Hier kannst du Businesslogik hinzufügen, z. B. für Statusvalidierung
}
