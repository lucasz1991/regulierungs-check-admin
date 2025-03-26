<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Die Felder, die massenweise zuweisbar sind.
     *
     * @var array
     */
    protected $fillable = [
        'shelf_rental_id',
        'invoice_identifier',
    ];

    /**
     * Beziehung: Eine Rechnung gehört zu einer Buchung.
     */
    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

}
