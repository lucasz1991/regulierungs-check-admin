<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'product_id',
        'shelf_id',
        'liked_customer_id'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function shelf()
    {
        return $this->belongsTo(Shelf::class);
    }

    public function likedCustomer()
    {
        return $this->belongsTo(Customer::class, 'liked_customer_id');
    }
}
