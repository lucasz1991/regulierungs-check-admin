<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetailInsuranceRating extends Model
{
    protected $fillable = [
        'insurance_id',
        'insurance_subtype_id',
        'type',
        'status',
        'fairness',
        'speed',
        'communication',
        'transparency',
        'total_score',
        'ai_comment',
        'ai_tags',
        'admin_comment',
    ];

    protected $casts = [
        'fairness'      => 'float',
        'speed'         => 'float',
        'communication' => 'float',
        'transparency'  => 'float',
        'total_score'   => 'float',
        'ai_tags'       => 'array', // wichtig für JSON
    ];

    public function insurance(): BelongsTo
    {
        return $this->belongsTo(Insurance::class);
    }

    public function insuranceSubtype(): BelongsTo
    {
        return $this->belongsTo(InsuranceSubtype::class); // Import stimmt so
    }
}
