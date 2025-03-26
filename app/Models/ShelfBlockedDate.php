<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShelfBlockedDate extends Model
{
    use HasFactory;

    // Tabelle, die mit dem Modell verknüpft ist
    protected $table = 'shelf_blocked_dates';

    // Spalten, die massenweise zugewiesen werden können
    protected $fillable = [
        'shelf_id',
        'retail_space_id',
        'blocked_date',
    ];

    // Falls Timestamps nicht verwendet werden, kannst du dies auf false setzen
    public $timestamps = true;

    // Optional: Beziehung zu 'Shelve' (Regale) und 'RetailSpace' (Verkaufsfläche)
    public function shelf()
    {
        return $this->belongsTo(Shelve::class, 'shelf_id');
    }

    public function retailSpace()
    {
        return $this->belongsTo(RetailSpace::class, 'retail_space_id');
    }
}
