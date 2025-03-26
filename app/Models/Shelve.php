<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\ShelfBlockedDate;

class Shelve extends Model
{
    use SoftDeletes;
    protected $fillable = ['retail_space_id', 'shelve_type_id', 'floor_number', 'shelve_id', 'position_x', 'position_y'];

    public function retailSpace()
    {
        return $this->belongsTo(RetailSpace::class);
    }

    public function shelveType()
    {
        return $this->belongsTo(ShelveType::class);
    }
    public function blockedDates()
    {
        return $this->hasMany(ShelfBlockedDate::class, 'shelf_id');
    }
}
