<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RetailSpace extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'location_id', 'layout', 'layout_elements', 'status', 'published_at'];

    protected $casts = [
        'layout' => 'array',
    ];
    
    public function shelves()
    {
        return $this->hasMany(Shelve::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}
