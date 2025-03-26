<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Notifications\ProductSoldNotification;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class Sale extends Model
{
    use HasFactory;

        /**
     * Status:
     *
     *      1 = OK
     *      2 = Auszahlung beantragt
     *      3 = Paid out
     *      4 = Storniert
     */

    protected $fillable = [
        'product_id',
        'customer_id',
        'rental_id',
        'date',
        'sale_price',
        'status',
        'net_sale_price'
    ];

    protected static function booted()
    {
        static::created(function ($sale) {
            // Hole den Kunden anhand der `customer_id`
            $user = $sale->product->customer->user;

            if ($user) {
                try {
                    // Provision aus der Settings-Tabelle abrufen (neueste gültige Provision)
                    $provisionSetting = Setting::where('key', 'LIKE', 'provision_%')
                    ->where('created_at', '<=', now()) // Neueste gültige Provision abrufen
                    ->orderBy('created_at', 'desc')
                    ->first();
                    $commissionRate = $provisionSetting ? json_decode($provisionSetting->value, true)['percentage'] : 0.16; // Standard 16%, falls keine gefunden wird
                    // Nettoverkaufswert berechnen (nach Abzug der Provision)
                    $sale->net_sale_price = $sale->sale_price * (1 - $commissionRate);
                    $sale->save(); // Netto-Wert speichern
                    // Sende die Notification an den Kunden
                    $user->notify(new ProductSoldNotification($sale));

                    Log::info("Notification für Verkauf ID {$sale->id} wurde erfolgreich gesendet.");
                } catch (\Exception $e) {
                    // Fehlerbehandlung
                    Log::error("Fehler beim Senden der Notification für Verkauf ID {$sale->id}: " . $e->getMessage());
                }
            }
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function rental()
    {
        return $this->belongsTo(ShelfRental::class, 'rental_id');
    }
}
