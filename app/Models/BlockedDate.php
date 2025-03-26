<?php



namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockedDate extends Model
{
    use HasFactory;

    // Tabelle, die mit dem Modell verknüpft ist
    protected $table = 'blocked_dates';

    // Spalten, die massenweise zugewiesen werden können
    protected $fillable = [
        'retail_space_id',
        'blocked_date',
        'blocked_period',
    ];

    // Falls Timestamps nicht verwendet werden, kannst du dies auf false setzen
    public $timestamps = true;

    // Optional: Beziehung zu 'RetailSpace' (Verkaufsfläche)
    public function retailSpace()
    {
        return $this->belongsTo(RetailSpace::class, 'retail_space_id');
    }
}
