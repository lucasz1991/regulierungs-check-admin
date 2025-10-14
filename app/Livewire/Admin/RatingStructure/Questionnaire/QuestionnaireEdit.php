<?php

namespace App\Livewire\Admin\RatingStructure\Questionnaire;

use Livewire\Component;
use App\Models\InsuranceSubtype;
use App\Models\RatingQuestion;
use App\Support\PivotSorter; // wichtig: gleicher Import wie im anderen Component

class QuestionnaireEdit extends Component
{
    public $insuranceSubTypeId;
    public $insuranceSubType;
    public $showModal = false;

    public $availableQuestions = [];
    public $assignedQuestions = [];
    public $questionToAdd = null;

    protected $listeners = [
        'open-formbuilder' => 'open',
        'reorderAssignedQuestions' => 'handleReorderAssignedQuestions', // identisch zu deinen anderen Reorder-Events
    ];

    public function open($id = null)
    {
        // exakt wie im anderen Component: einmal hart resetten
        $this->reset();

        if ($id) {
            // Basisdaten laden
            $this->insuranceSubType = InsuranceSubtype::with(['ratingQuestions' => function ($q) {
                $q->orderBy('insurance_subtype_rating_question.order_column');
            }])->findOrFail($id);

            $this->insuranceSubTypeId = $this->insuranceSubType->id;

            // Zugeordnete (mit Pivot-Sortierung)
            $this->assignedQuestions = $this->insuranceSubType->ratingQuestions
                ->map(fn($q) => [
                    'id'            => $q->id,
                    'title'         => $q->title,
                    'type'          => $q->type,
                    'order_column'  => $q->pivot->order_column,
                ])
                ->values()
                ->toArray();

            // Verfügbare = alle, die NICHT zugeordnet sind
            $assignedIds = collect($this->assignedQuestions)->pluck('id');
            $this->availableQuestions = RatingQuestion::whereNotIn('id', $assignedIds)->orderBy('title')->get();
        }

        $this->showModal = true;
    }

    public function save()
    {
        $this->assignedQuestions = collect($this->assignedQuestions)
            ->values()
            ->map(fn($it, $i) => array_merge($it, ['order_column' => $i]))
            ->toArray();

        $syncData = collect($this->assignedQuestions)
            ->mapWithKeys(fn ($item, $index) => [$item['id'] => ['order_column' => $index]])
            ->toArray();

        $this->insuranceSubType()->ratingQuestions()->sync($syncData);

        $this->dispatch('refreshQuestionnaires');
        $this->showModal = false;
    }


    public function addQuestion()
    {
        if (!$this->questionToAdd) return;

        $question = $this->availableQuestions->firstWhere('id', $this->questionToAdd)
                 ?? RatingQuestion::find($this->questionToAdd);

        if (!$question) return;

        if (!collect($this->assignedQuestions)->pluck('id')->contains($question->id)) {
            $this->assignedQuestions[] = [
                'id'           => $question->id,
                'title'        => $question->title,
                'type'         => $question->type,
                'order_column' => count($this->assignedQuestions),
            ];
        }

        // Auswahl zurücksetzen und Available neu bilden
        $this->questionToAdd = null;
        $this->availableQuestions = RatingQuestion::whereNotIn('id', collect($this->assignedQuestions)->pluck('id'))
            ->orderBy('title')->get();
    }

    public function removeQuestion($id)
    {
        $this->assignedQuestions = collect($this->assignedQuestions)
            ->reject(fn ($q) => $q['id'] == $id)
            ->values()
            ->toArray();

        // Available neu bilden
        $this->availableQuestions = RatingQuestion::whereNotIn('id', collect($this->assignedQuestions)->pluck('id'))
            ->orderBy('title')->get();
    }

    public function handleReorderAssignedQuestions($item, $position)
    {
        if (!$this->insuranceSubTypeId || $item === null || $position === null) return;

        PivotSorter::reorder(
            \App\Models\InsuranceSubtype::find($this->insuranceSubTypeId),
            'ratingQuestions',
            $item,
            (int)$position,
            'order_column',
            $this->assignedQuestions,
            fn($q, string $pivotKey) => [
                'id'      => $q->id,
                'title'   => $q->title,
                'type'    => $q->type,
                $pivotKey => $q->pivot->{$pivotKey},
            ]
        );
    }


    // kleiner Helper wie bei save()
    protected function insuranceSubType(): InsuranceSubtype
    {
        // frisch laden, falls nötig
        return $this->insuranceSubType ??= InsuranceSubtype::findOrFail($this->insuranceSubTypeId);
    }

    public function render()
    {
        return view('livewire.admin.rating-structure.questionnaire.questionnaire-edit');
    }
}
