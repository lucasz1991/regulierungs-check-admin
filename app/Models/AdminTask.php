<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_type',
        'description',
        'status',
        'assigned_to',
        'shelf_rental_id',
        'completed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
    ];

    // Statuswerte als Konstante
    public const STATUS_OPEN = 0;
    public const STATUS_IN_PROGRESS = 1;
    public const STATUS_COMPLETED = 2;

    /**
     * Beziehung zum Benutzer (Admin), der die Aufgabe bearbeitet.
     */
    public function assignedAdmin()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Beziehung zur Regalmiete.
     */
    public function shelfRental()
    {
        return $this->belongsTo(ShelfRental::class, 'shelf_rental_id');
    }

    /**
     * Gibt den Status als lesbaren Text zurück.
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => '⚠️',
            self::STATUS_IN_PROGRESS => '⏳',
            self::STATUS_COMPLETED => '✅',
            default => 'Unbekannt',
        };
    }
}
