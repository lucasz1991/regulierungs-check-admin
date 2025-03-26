<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Notifications\PayoutCompletedNotification;


class Payout extends Model
{
    use HasFactory;

    /**
     * Die Felder, die massenzugewiesen werden können.
     */
    protected $fillable = [
        'customer_id',       // Verknüpfung mit dem Kunden
        'shelf_rental_id',   // Verknüpfung mit einer Regalbuchung
        'amount',            // Auszahlungsbetrag
        'status',            // Status der Auszahlung (z. B. "abgeschlossen" oder "offen")
        'payout_details',    // JSON-Feld für Details (z. B. Bank, Transaktions-ID)
    ];

    /**
     * Die Felder, die automatisch in Arrays oder Objekte umgewandelt werden.
     */
    protected $casts = [
        'payout_details' => 'array', // JSON-Feld automatisch in ein Array umwandeln
        'status' => 'boolean',      // Status als Boolean (true/false)
    ];

    /**
     * Beziehung zum Kunden.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Beziehung zur Regalbuchung.
     */
    public function shelfRental()
    {
        return $this->belongsTo(ShelfRental::class, 'shelf_rental_id');
    }


        /**
     * Automatisches Senden der Notification, wenn `status` auf `true` gesetzt wird.
     */
    protected static function boot()
    {
        parent::boot();
        static::updating(function ($payout) {
            if ($payout->isDirty('status') && $payout->status === true) {
                // Prüfen, ob der Kunde eine gültige Benutzerverknüpfung hat
                if ($payout->customer && $payout->customer->user) {
                    $payout->customer->user->notify(new PayoutCompletedNotification($payout));
                }
            }
        });
    }
}
