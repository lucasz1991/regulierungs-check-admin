<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminShelfBlockedDate extends Model
{
    use HasFactory;

    // Tabelle, die mit diesem Modell verknüpft ist
    protected $table = 'admin_shelf_blocked_dates';

    // Massenweise zuweisbare Felder
    protected $fillable = [
        'shelve_id',
        'retail_space_id',
        'start_date',
        'end_date',
    ];

    // Beziehungen zu anderen Modellen
    public function shelve()
    {
        return $this->belongsTo(Shelve::class, 'shelve_id'); // Beziehung zu Shelve-Modell
    }

    public function retailSpace()
    {
        return $this->belongsTo(RetailSpace::class, 'retail_space_id'); // Beziehung zu RetailSpace-Modell
    }
}
