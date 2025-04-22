<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Insurance;
use App\Models\RatingQuestion;
use App\Models\RatingQuestionnaireVersion;

class InsuranceType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'average_rating_speed',
        'average_rating_fairness',
        'average_rating_service',
        'is_active',
        'order_id',
    ];

    public function insurances()
    {
        return $this->belongsToMany(Insurance::class, 'insurance_insurance_type')
                    ->withPivot('order_column')
                    ->orderBy('insurance_insurance_type.order_column');
    }

    public function ratingQuestions()
    {
        return $this->belongsToMany(RatingQuestion::class)
                    ->withPivot('order_column', 'notes')
                    ->orderBy('insurance_type_rating_question.order_column');
    }

    public function latestVersion()
    {
        return $this->hasOne(RatingQuestionnaireVersion::class)->latestOfMany('version_number');
    }

}
