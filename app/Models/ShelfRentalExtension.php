<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShelfRentalExtension extends Model
{
    use HasFactory;

    protected $fillable = [
        'shelf_rental_id',
        'user_id',
        'previous_end_date',
        'new_end_date',
        'amount_paid',
        'is_admin',
        'invoice_id',
        'extension_details',
    ];

    protected $casts = [
        'previous_end_date' => 'date',
        'new_end_date' => 'date',
        'is_admin' => 'boolean',
        'extension_details' => 'array', // JSON-Feld als Array nutzen
    ];

    public function shelfRental()
    {
        return $this->belongsTo(ShelfRental::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
