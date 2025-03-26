<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ShelveType extends Model
{
    use SoftDeletes;
    protected $fillable = ['name', 'width', 'length', 'description', 'image_path'];

    public function shelves()
    {
        return $this->hasMany(Shelve::class);
    }
}
