<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\InsuranceType;

class Insurance extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'initials',
        'color',
        'is_active',
        'order_id',
    ];

    public function insuranceTypes()
    {
        return $this->belongsToMany(InsuranceType::class, 'insurance_insurance_type')
                    ->withPivot('order_column')
                    ->orderBy('insurance_insurance_type.order_column');
    }
    
}
