<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'customer_id',
        'rental_id',
        'date',
        'sale_price',
        'net_sale_price',
        'status',
    ];

    protected $casts = [
        'date' => 'datetime',
        'sale_price' => 'decimal:2',
        'net_sale_price' => 'decimal:2',
        'status' => 'integer',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function rental(): BelongsTo
    {
        return $this->belongsTo(ShelfRental::class, 'rental_id');
    }
}
