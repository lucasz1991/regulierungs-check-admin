<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffInvitation extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'token_hash',
        'team_id',
        'invited_by',
        'role',
        'position',
        'expires_at',
        'accepted_at',
    ];

    protected $hidden = ['token_hash'];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function isUsable(): bool
    {
        return $this->accepted_at === null && $this->expires_at->isFuture();
    }

    public static function tokenHash(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }
}
