<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Shelve;
use App\Models\Customer;
use App\Models\User;
use App\Models\Location;
use App\Models\Product;
use App\Models\Sale;


class ShelfRental extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * 
     * Status:
     *
     *      1 = Bevorstehend nicht eingecheckt
     *      2 = Aktiv
     *      3 = Abgelaufen nicht abgerechnet
     *      4 = Abgelaufen und abgerechnet
     *      5 = Bevorstehend und eingecheckt
     *      6 = Aktiv nicht eingecheckt
     *      7 = Storniert
     *      8 = Abgelaufen und abrechnung beantragt
     * 
     */


    protected $fillable = [
        'shelf_id',
        'customer_id', 
        'rental_start',
        'rental_end',
        'period',
        'status',
        'total_price',
        'payment_method',
        'rental_bill_url',
        'complete_bill_url',
        'discount',
    ];



    /**
     * Berechnet den Gesamtumsatz für die Regalmiete basierend auf den Verkäufen in der `sales`-Tabelle.
     *
     * @return float
     */
    public function getRevenue(): float
    {
        return Sale::where('rental_id', $this->id)
            ->sum('sale_price'); // Summiere alle Verkaufspreise
    }

    /**
     * Berechnet die Einkünfte des Kunden nach Abzug der Provision
     *
     * @return float
     */
    public function getCustomerEarnings(): float
    {
        return Sale::where('rental_id', $this->id)
            ->sum('net_sale_price'); // Summiere alle Verkaufspreise
    }


    /**
     * Berechnet den Gesamtbetrag, der bereits an den Kunden ausgezahlt wurde.
     *
     * @return float
     */
    public function getPaidOutAmount(): float
    {
        return Sale::where('rental_id', $this->id)
            ->where('status', 2)
            ->sum('net_sale_price');
    }


    public function shelf()
    {
        return $this->belongsTo(Shelve::class);
    }

    public function customer() 
    {
        return $this->belongsTo(Customer::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'shelf_rental_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'booking_id');
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class, 'shelf_rental_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'rental_id');
    }

}
