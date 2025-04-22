<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\InsuranceType;
use App\Models\RatingQuestionOption;

class RatingQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'question_text',
        'type',
        'is_required',
        'meta',
        'help_text',
        'default_value',
        'is_active',
        'frontend_title',
        'frontend_description',
        'weight',
        'input_constraints',
        'read_only',
        'tags',
    ];

    protected $casts = [
        'meta' => 'array',
        'input_constraints' => 'array',
        'tags' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'read_only' => 'boolean',
    ];

    public function insuranceTypes()
    {
        return $this->belongsToMany(InsuranceType::class)
                    ->withPivot('order_column', 'notes', 'visibility_conditions')
                    ->orderBy('insurance_type_rating_question.order_column');
    }


    public function options()
    {
        return $this->hasMany(RatingQuestionOption::class)->orderBy('order_column');
    }
}
