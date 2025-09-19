<?php

namespace App\Livewire\Admin\Reviews;

use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Validation\Rule;
use App\Models\ClaimRating;
use App\Models\InsuranceType;
use App\Models\InsuranceSubtype;
use App\Models\Insurance;

class EditClaimRatingModal extends Component
{
    public bool $showFormModal = false;

    public ?int $ratingId = null;
    public ?ClaimRating $rating = null;

    // Stammdaten
    public ?int $insuranceTypeId = null;
    public ?int $insuranceSubTypeId = null;
    public ?int $insuranceId = null;

    // Status
    public ?string $regulationType = null; // 'vollzahlung'|'teilzahlung'|'ablehnung'|'austehend'
    public array $regulationDetails = [];
    public ?string $regulationComment = null;

    // Vertrag
    public array $contractDetails = [
        'contract_deductible_amount' => null,
        'claim_amount' => null,
        'claim_settlement_amount' => null,
        'textarea_value' => null,
    ];

    // Zeitraum
    public ?string $selectedDates = null; // "dd.mm.yyyy" oder "dd.mm.yyyy bis dd.mm.yyyy"
    public ?string $started_at = null;
    public ?string $ended_at = null;
    public bool  $is_closed = true;

    // Dynamische Fragen
    public array $variableQuestions = [];
    public array $answers = [];

    // Select-Listen
    public $types = [];
    public $insuranceSubTypes = [];
    public $insurances = [];

    // Flags
    public bool $thirdPartyInsuranceAllowed = false;
    public bool $thirdPartyInsurance = false;

    public function mount(?int $ratingId = null, bool $open = false): void
    {
        $this->ratingId = $ratingId;

        // Typen laden (Subtypes/Insurers kommen erst dynamisch)
        $this->types = InsuranceType::query()
            ->orderBy('name')
            ->get();

        if ($ratingId) {
            $this->loadRating($ratingId);
        }

        $this->updatedInsuranceTypeId();
        $this->updatedInsuranceSubTypeId();

        if ($open) {
            $this->showFormModal = true;
        }
    }

    #[On('edit-claim-rating')]
    public function onOpen(int $ratingId): void
    {
        $this->resetValidation();
        $this->loadRating($ratingId);
        $this->showFormModal = true;
    }

    protected function loadRating(int $id): void
    {
        $this->rating = ClaimRating::with(['insuranceSubtype','insurance','insuranceType'])->findOrFail($id);

        // 1) IDs & Stammdaten aus answers
        $ans = (array) ($this->rating->answers ?? []);

        $this->insuranceTypeId    = (int) data_get($ans, 'insuranceTypeId');
        $this->insuranceSubTypeId = (int) data_get($ans, 'insuranceSubTypeId');
        $this->insuranceId        = (int) data_get($ans, 'insuranceId');

        // 2) Status / Vertrag aus answers
        $this->regulationType    = data_get($ans, 'regulationType'); // 'vollzahlung'|'teilzahlung'|'ablehnung'|'austehend'
        $regDetail               = (array) data_get($ans, 'regulationDetail', []);
        $this->regulationDetails = (array) ($regDetail['selected_values'] ?? []);
        $this->regulationComment = $regDetail['textarea_value'] ?? null;

        $contract                = (array) data_get($ans, 'contractDetails', []);
        $this->contractDetails   = array_replace($this->contractDetails, $contract);

        // 3) Zeitraum aus answers
        $dateArr      = (array) data_get($ans, 'selectedDates', []);
        $this->started_at = $dateArr['started_at'] ?? null; // bereits dd.mm.YY
        $this->ended_at   = $dateArr['ended_at']   ?? null; // bereits dd.mm.YY
        $this->is_closed  = (bool) data_get($ans, 'is_closed', !empty($this->ended_at));
        $this->selectedDates = $this->composeSelectedDates($this->started_at, $this->ended_at);

        // 4) Third-party
        $this->thirdPartyInsurance = (bool) data_get($ans, 'thirdPartyInsurance', false);
        $this->thirdPartyInsuranceAllowed = $this->detectThirdPartyAllowed($this->insuranceSubTypeId);

        // 5) Zusatzfragen & Antworten
        $this->variableQuestions = $this->loadVariableQuestions($this->insuranceSubTypeId);
        $this->answers = $ans; // komplette answers hinein, damit alle keys verfügbar sind

        // 6) abhängige Listen
        $this->updatedInsuranceTypeId();
        $this->updatedInsuranceSubTypeId();
    }


    protected function composeSelectedDates(?string $start, ?string $end): ?string
    {
        if (!$start) return null;
        return $this->is_closed && $end ? ($start . ' bis ' . $end) : $start;
    }

    protected function detectThirdPartyAllowed(?int $subtypeId): bool
    {
        if (!$subtypeId) return false;
        $sub = InsuranceSubtype::find($subtypeId);
        // unterstützt beide möglichen Spalten
        return (bool)($sub->allow_third_party ?? $sub->third_party_allowed ?? false);
    }

    protected function loadVariableQuestions(?int $subtypeId): array
    {
        if (!$subtypeId) return [];

        $sub = InsuranceSubtype::with(['ratingQuestions' => function ($q) {
            $q->orderBy('insurance_subtype_rating_question.order_column');
        }])->find($subtypeId);

        $items = $sub?->ratingQuestions?->map(function ($q) {
            $visibility = $q->visibility_condition ?? $q->pivot->visibility_conditions ?? [];
            if (!is_array($visibility)) {
                $visibility = json_decode($visibility, true) ?: [];
            }
            return [
                'id'                    => $q->id,
                'title'                 => $q->title,
                'question_text'         => $q->question_text,
                'type'                  => $q->type,
                'is_required'           => (bool)($q->is_required ?? $q->pivot->is_required ?? false),
                'input_constraints'     => (array)($q->input_constraints ?? $q->pivot->input_constraints ?? []),
                'visibility_condition'  => $visibility,
            ];
        })->all() ?? [];

        // Sichtbarkeit gegen thirdPartyInsurance prüfen
        $items = array_values(array_filter($items, function ($q) {
            if (isset($q['visibility_condition']['thirdPartyInsurance'])) {
                return $this->thirdPartyInsurance === (bool)$q['visibility_condition']['thirdPartyInsurance'];
            }
            return true;
        }));

        return $items;
    }

    public function updatedInsuranceTypeId(): void
    {
        // Subtypes & Insurances immer vom TYPE her laden
        $type = $this->insuranceTypeId
            ? InsuranceType::with(['subtypes', 'insurances'])->find($this->insuranceTypeId)
            : null;

        // Subtypes
        $this->insuranceSubTypes = $type?->subtypes ?? collect();
        if ($this->insuranceSubTypeId && $this->insuranceSubTypes->where('id', $this->insuranceSubTypeId)->isEmpty()) {
            $this->insuranceSubTypeId = null;
        }

        // Insurers (KEINE Spalte insurance_type_id auf insurances verwenden!)
        $this->insurances = $type?->insurances ?? collect();
        if ($this->insuranceId && $this->insurances->where('id', $this->insuranceId)->isEmpty()) {
            $this->insuranceId = null;
        }
    }

    public function updatedInsuranceSubTypeId(): void
    {
        if (!$this->insuranceSubTypeId) {
            // nur Subtype-abhängige Dinge zurücksetzen
            $this->thirdPartyInsuranceAllowed = false;
            $this->variableQuestions = [];
            return;
        }

        // Fremdversicherung-Flag (beide Spaltennamen unterstützen)
        $this->thirdPartyInsuranceAllowed = $this->detectThirdPartyAllowed($this->insuranceSubTypeId);

        // Dynamische Fragen laden & Antworten initialisieren
        $this->variableQuestions = $this->loadVariableQuestions($this->insuranceSubTypeId);
        foreach ($this->variableQuestions as $q) {
            $key = $q['title'];
            if (!array_key_exists($key, $this->answers)) {
                $this->answers[$key] = null;
            }
        }
    }

    public function updatedInsuranceId(): void
    {
        // NOP: nur sicherstellen, dass ID zu Liste passt
        if ($this->insuranceId && collect($this->insurances)->where('id', $this->insuranceId)->isEmpty()) {
            $this->insuranceId = null;
        }
    }

    protected function rules(): array
    {
        return [
            'insuranceTypeId'     => ['required', Rule::exists('insurance_types','id')],
            'insuranceSubTypeId'  => ['required', Rule::exists('insurance_subtypes','id')],
            'insuranceId'         => ['required', Rule::exists('insurances','id')],

            'regulationType'      => ['required', Rule::in(['vollzahlung','teilzahlung','ablehnung','austehend'])],
            'regulationDetails'   => ['array'],
            'regulationComment'   => ['nullable','string','max:255'],

            'contractDetails.contract_deductible_amount' => ['nullable','string','max:50'],
            'contractDetails.claim_amount'               => ['required','string','max:50'],
            'contractDetails.claim_settlement_amount'    => [
                Rule::requiredIf(fn()=>in_array($this->regulationType, ['vollzahlung','teilzahlung'])),
                'nullable','string','max:50'
            ],
            'contractDetails.textarea_value'             => ['nullable','string','max:255'],

            'selectedDates'      => ['nullable','string','max:100'],
            'started_at'         => ['required','string','max:20'],
            'ended_at'           => [Rule::requiredIf(fn()=> $this->is_closed === true),'nullable','string','max:20'],

            'answers'            => ['array'],
        ];
    }

    protected function messages(): array
    {
        return [
            'insuranceTypeId.required' => 'Bitte eine Versicherungskategorie wählen.',
            'insuranceSubTypeId.required' => 'Bitte eine Versicherungsart wählen.',
            'insuranceId.required' => 'Bitte eine Versicherungsgesellschaft wählen.',
            'regulationType.required' => 'Bitte einen Fallstatus wählen.',
            'contractDetails.claim_amount.required' => 'Bitte die Schadenshöhe angeben.',
            'contractDetails.claim_settlement_amount.required' => 'Bitte die Regulierungshöhe angeben.',
            'started_at.required' => 'Bitte ein Startdatum wählen.',
            'ended_at.required' => 'Bitte ein Enddatum wählen.',
        ];
    }

    public function parseSelectedDates(): void
    {
        $raw = trim((string)$this->selectedDates);
        if ($raw === '') {
            $this->started_at = null;
            $this->ended_at = null;
            return;
        }
        if (str_contains($raw, 'bis')) {
            [$s, $e] = array_map('trim', explode('bis', $raw, 2));
            $this->started_at = $s ?: null;
            $this->ended_at   = $e ?: null;
            $this->is_closed  = true;
        } else {
            $this->started_at = $raw;
            $this->ended_at   = null;
            $this->is_closed  = false;
        }
    }

    public function update(): void
    {
        $this->parseSelectedDates();
        $this->validate();

        $rating = $this->rating ?? ClaimRating::findOrFail($this->ratingId);
        $ans    = (array) ($rating->answers ?? []);

        // IDs + Stammdaten
        $ans['insuranceTypeId']    = $this->insuranceTypeId;
        $ans['insuranceSubTypeId'] = $this->insuranceSubTypeId;
        $ans['insuranceId']        = $this->insuranceId;

        // Status / Vertrag
        $ans['regulationType']   = $this->regulationType;
        $ans['regulationDetail'] = [
            'selected_values' => array_values($this->regulationDetails ?? []),
            'textarea_value'  => $this->regulationComment,
        ];
        $ans['contractDetails']  = $this->contractDetails;

        // Zeitraum
        $ans['selectedDates'] = [
            'started_at' => $this->started_at,               // dd.mm.YY
            'ended_at'   => $this->is_closed ? $this->ended_at : null,
        ];
        $ans['is_closed'] = $this->is_closed;

        // Third-party
        $ans['thirdPartyInsurance'] = $this->thirdPartyInsurance;

        // Zusatzfragen (bereits in $this->answers enthalten) in $ans mergen,
        // aber Kernkeys nicht überschreiben:
        foreach ($this->answers as $k => $v) {
            if (!in_array($k, [
                'insuranceTypeId','insuranceSubTypeId','insuranceId',
                'regulationType','regulationDetail','contractDetails',
                'selectedDates','is_closed','thirdPartyInsurance'
            ], true)) {
                $ans[$k] = $v;
            }
        }

        // Speichern: nur existierende Spalten + answers
        $rating->fill([
            'insurance_type_id'    => $this->insuranceTypeId,
            'insurance_subtype_id' => $this->insuranceSubTypeId,
            'insurance_id'         => $this->insuranceId,
            'answers'              => $ans,
        ])->save();

        $this->showFormModal = false;
        $this->resetValidation();
        $this->dispatch('claim-rating-updated');
    }


    protected function deToIsoDate(?string $d): ?string
    {
        if (!$d) return null;
        [$day,$month,$year] = array_pad(explode('.', $d), 3, null);
        if (!$day || !$month || !$year) return null;
        return sprintf('%04d-%02d-%02d', (int)$year, (int)$month, (int)$day);
    }

    public function render()
    {
        return view('livewire.admin.reviews.edit-claim-rating-modal');
    }
}
