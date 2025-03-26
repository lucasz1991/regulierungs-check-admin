<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Review extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'reviewed_customer_id',
        'creator_customer_id',
        'review_text',
        'rating',
        'date',
    ];

    public function reviewedCustomer()
    {
        return $this->belongsTo(Customer::class, 'reviewed_customer_id');
    }

    public function creatorCustomer()
    {
        return $this->belongsTo(Customer::class, 'creator_customer_id');
    }
}
