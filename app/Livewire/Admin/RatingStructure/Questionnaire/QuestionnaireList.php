<?php

namespace App\Livewire\Admin\RatingStructure\Questionnaire;

use Livewire\Component;
use App\Models\InsuranceSubtype;
use App\Models\RatingQuestionnaireVersion;


class QuestionnaireList extends Component
{
    public $types = [];

    protected $listeners = [
        'refreshQuestionnaires' => 'loadData',
    ];

    public function mount()
    {
        $this->loadData();
    }

    public function loadData()
    {
        $this->types = InsuranceSubtype::with([
            'ratingQuestions' => function ($query) {
                $query->orderBy('insurance_subtype_rating_question.order_column');
            },
            'latestVersion'
        ])->get();
    
        foreach ($this->types as $type) {
            // Prüfe auf existierende Version
            $existing = RatingQuestionnaireVersion::where('insurance_subtype_id', $type->id)->first();
    
            if (!$existing) {
                RatingQuestionnaireVersion::create([
                    'insurance_subtype_id' => $type->id,
                    'version_number' => 1,
                    'snapshot' => $type->ratingQuestions->map(function ($q) {
                        return [
                            'id' => $q->id,
                            'title' => $q->title,
                            'type' => $q->type,
                            'pivot' => [
                                'order_column' => $q->pivot->order_column,
                                'visibility_conditions' => $q->pivot->visibility_conditions ?? null,
                            ]
                        ];
                    })->toArray(),
                    'is_active' => true,
                ]);
            }
        }
    }

    public function toggleActiveVersion($typeId)
    {
        $latest = RatingQuestionnaireVersion::where('insurance_subtype_id', $typeId)
            ->orderByDesc('version_number')
            ->first();

        if ($latest) {
            $latest->update([
                'is_active' => !$latest->is_active
            ]);

            $this->loadData();
        }
    }


    public function render()
    {
        return view('livewire.admin.rating-structure.questionnaire.questionnaire-list');
    }
}
