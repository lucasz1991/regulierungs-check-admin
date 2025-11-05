<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\InsuranceType;
use App\Jobs\AnalyzeInsuranceOnlineViaGpt;
use App\Jobs\Insurance\AnalyzeInsuranceClaimRatings;

use App\Models\Setting;


class Insurance extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'helptext',
        'style',
        'initials',
        'color',
        'logo',
        'is_active',
        'order_id',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'style' => 'array',
        'order_id' => 'integer',
    ];
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function claimRatings()
    {
        return $this->hasMany(ClaimRating::class);
    }

    public function analyzeInsuranceOnlineViaGpt()
    {
        AnalyzeInsuranceOnlineViaGpt::dispatch($this);
    }

    public function insuranceAnalyzeClaimRatingsViaGpt(?int $insuranceSubtypeId = null): void
    {
        AnalyzeInsuranceClaimRatings::dispatch($this->id, $insuranceSubtypeId);
    }

    //  URL zum Bild (oder Fallback)
    public function getLogoImageUrlAttribute()
    {
        $apiUrl = Setting::where('key', 'base_api_url')->value('value');
        return $this->logo
            ? $apiUrl . '/storage/' . $this->logo : null;
    }
   
    public function insuranceTypes()
    {
        return $this->belongsToMany(InsuranceType::class, 'insurance_insurance_type')
                    ->withPivot('order_column')
                    ->orderBy('insurance_insurance_type.order_column');
    }
}
