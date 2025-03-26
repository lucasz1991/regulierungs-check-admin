<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bonus extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'type',
        'value',
        'valid_from',
        'valid_until',
        'booking_start_from',
        'booking_start_until',
        'periods', // JSON-Feld für Buchungsperioden
        'customer_requirement',
        'validity_period',
        'user_id',
        'status',
        'is_redeemable',
    ];

    protected $casts = [
        'valid_from' => 'date',
        'valid_until' => 'date',
        'booking_start_from' => 'date',
        'booking_start_until' => 'date',
        'periods' => 'array', // JSON-Feld wird als Array gecastet
        'is_redeemable' => 'boolean',
        'status' => 'integer',
    ];

    /**
     * Gibt zurück, ob der Bonus für eine bestimmte Periode gültig ist.
     */
    public function isValidForPeriod($period)
    {
        return in_array($period, $this->periods ?? []);
    }

    /**
     * Überprüft, ob der Bonus aktiv ist.
     */
    public function isActive()
    {
        return $this->status === 1;
    }
}
