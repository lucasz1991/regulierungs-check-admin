<?php

namespace App\Livewire\Admin\Reviews;

use Livewire\Component;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use App\Models\Setting;
use App\Models\InsuranceSubtype;
use App\Models\InsuranceType;
use App\Models\Insurance;
use App\Models\RatingQuestionnaireVersion;
use App\Models\ClaimRating;

class AnonymousReviewForm extends Component
{
    public $types = [];
    public $insuranceTypeId = null;
    public $insuranceType;
    public $insuranceSubTypeId = null;
    public $insuranceSubType;

    public $thirdPartyInsuranceAllowed = false;
    public $thirdPartyInsurance = false;
    public $thirdPartyInsuranceHasContact = false; // NEU

    public $insuranceSubTypes = [];
    public $insurances = [];
    public $insuranceId = null;
    public $insurance;

    public $is_closed = null;
    public $regulationType = null;
    public $regulationDetails = [];
    public $regulationComment = null;

    public $contractDetails = [
        'contract_coverage_amount'   => null,
        'contract_deductible_amount' => null,
        'claim_amount'               => null,
        'claim_settlement_amount'    => null,
        'textarea_value'             => null,
    ];

    public $selectedDates = null;
    public $started_at = null;
    public $setting_available_started_at = null;
    public $ended_at = null;
    public $setting_available_ended_at = null;

    public $showFormModal = false;
    public $step = 0;
    public $standardSteps = 7;
    public $totalSteps = 0;

    public $standardQuestions = [];
    public $questions = [];
    public $variableQuestions = [];
    public $answers = [];

    protected $listeners = [
        'showFormModal' => 'showFormModal',
    ];

    public function mount()
    {
        $this->types = InsuranceType::whereHas('subtypes', function ($query) {
            $query->whereHas('latestVersion', fn($q) => $q->where('is_active', true));
        })->get();

        $this->setting_available_started_at = Setting::getValue('rating_form', 'available_started_at') ?? null;
        $this->setting_available_ended_at   = Setting::getValue('rating_form', 'available_ended_at') ?? null;

        // Standardfragen wie im Kunden-Form
        $this->standardQuestions = collect([
            (object)[
                'id' => 1, 'title' => 'insuranceTypeId',
                'question_text' => ' Bitte wählen Sie den Versicherungstyp aus.',
                'type' => 'select', 'is_required' => true, 'meta' => null,
                'help_text' => null, 'default_value' => null, 'is_active' => true,
                'frontend_title' => '', 'frontend_description' => '',
                'weight' => 1, 'input_constraints' => [], 'read_only' => false, 'tags' => [],
            ],
            (object)[
                'id' => 2, 'title' => 'insuranceSubTypeId',
                'question_text' => ' Bitte wählen Sie die Versicherungsart aus.',
                'type' => 'select', 'is_required' => true, 'meta' => null,
                'help_text' => null, 'default_value' => null, 'is_active' => true,
                'frontend_title' => '', 'frontend_description' => '',
                'weight' => 2, 'input_constraints' => [], 'read_only' => false, 'tags' => [],
            ],
            (object)[
                'id' => 3, 'title' => 'insuranceId',
                'question_text' => ' Bitte wählen Sie die Versicherung aus.',
                'type' => 'select', 'is_required' => true, 'meta' => null,
                'help_text' => null, 'default_value' => null, 'is_active' => true,
                'frontend_title' => '', 'frontend_description' => '',
                'weight' => 3, 'input_constraints' => [], 'read_only' => false, 'tags' => [],
            ],
            (object)[
                'id' => 4, 'title' => 'regulationType',
                'question_text' => 'Wie wurde der Schaden reguliert?',
                'type' => 'select', 'is_required' => true, 'meta' => null,
                'help_text' => 'Bitte wählen Sie eine Option aus. Falls der Fall noch nicht abgeschlossen ist, wählen Sie "Austehend".',
                'default_value' => null, 'is_active' => true,
                'frontend_title' => '', 'frontend_description' => '',
                'weight' => 4, 'input_constraints' => [], 'read_only' => false, 'tags' => [],
            ],
            (object)[
                'id' => 5, 'title' => 'regulationDetail',
                'question_text' => 'Bitte geben Sie Details zur Regulierung an.',
                'type' => 'radio-textarea', 'is_required' => false, 'meta' => null,
                'help_text' => 'Wenn Sie "Andere Gründe" auswählen, geben Sie bitte Details im Textfeld an.  ',
                'default_value' => null, 'is_active' => true,
                'frontend_title' => '', 'frontend_description' => '',
                'weight' => 4, 'input_constraints' => [], 'read_only' => false, 'tags' => [],
            ],
            (object)[
                'id' => 6, 'title' => 'contractDetails',
                'question_text' => 'Bitte geben Sie Details zum Versicherungs Vertrag an. ',
                'type' => 'radio-textarea', 'is_required' => false, 'meta' => null,
                'help_text' => 'Hier wird der Vertrag der Versicherung abgefragt, Deckungssumme, Selbstbeteiligung, Schadenshöhe, Regulierungshöhe und für Zusätzliche Leistungen und Extras gibt es ein Textfeld.',
                'default_value' => null, 'is_active' => true,
                'frontend_title' => '', 'frontend_description' => '',
                'weight' => 4, 'input_constraints' => [], 'read_only' => false, 'tags' => [],
            ],
            (object)[
                'id' => 7, 'title' => 'selectedDates',
                'question_text' => 'In welchem Zeitraum wurde der Fall reguliert?',
                'type' => 'date', 'is_required' => true, 'meta' => null,
                'help_text' => null, 'default_value' => null, 'is_active' => true,
                'frontend_title' => '', 'frontend_description' => '',
                'weight' => 5, 'input_constraints' => [], 'read_only' => false, 'tags' => [],
            ],
        ]);

        $this->questions = collect($this->standardQuestions);
        $this->answers   = array_fill_keys($this->questions->pluck('title')->toArray(), null);
    }

    public function showFormModal() { $this->showFormModal = true; }

    public function updatedInsuranceTypeId()
    {
        if (is_array($this->insuranceTypeId)) {
            $this->insuranceTypeId = $this->insuranceTypeId['value'];
        }

        $this->insuranceType = InsuranceType::find($this->insuranceTypeId);

        if (!$this->insuranceType) {
            $this->insuranceSubTypeId = null;
            $this->insuranceSubTypes  = [];
            $this->insurances         = [];
            $this->insuranceId        = null;
            $this->insurance          = null;
            $this->resetDates();
            return;
        }

        $this->insuranceSubTypes = $this->insuranceType->subtypes()->get();
        $this->answers['insuranceTypeId'] = $this->insuranceTypeId;
    }

    public function updatedInsuranceSubTypeId()
    {
        if (is_array($this->insuranceSubTypeId)) {
            $this->insuranceSubTypeId = $this->insuranceSubTypeId['value'];
        }

        if (!$this->insuranceSubTypeId) {
            $this->insuranceId = null;
            $this->insurances  = [];
            return;
        }

        $this->insuranceSubType              = InsuranceSubtype::find($this->insuranceSubTypeId);
        $this->thirdPartyInsuranceAllowed    = $this->insuranceSubType?->allow_third_party ?? false;
        $this->thirdPartyInsurance           = false;
        $this->thirdPartyInsuranceHasContact = false; // NEU: immer zurücksetzen

        $this->answers['thirdPartyInsurance']           = false;
        $this->answers['thirdPartyInsuranceHasContact'] = false; // NEU
        $this->answers['insuranceSubTypeId']            = $this->insuranceSubTypeId;

        // konsistent zur Kunden-Version: nur aktive Versicherer
        $this->insurances = $this->insuranceSubType
            ? $this->insuranceSubType->insurances()->where('is_active', 1)->get()
            : collect();

        // Dynamische Fragen erst später laden (Schritt 5), wie im Kunden-Form
        // -> hier NICHT automatisch loadQuestions() aufrufen
    }

    public function updatedThirdPartyInsurance()
    {
        $this->answers['thirdPartyInsurance'] = $this->thirdPartyInsurance;
        $this->thirdPartyInsuranceHasContact  = false;              // NEU
        $this->answers['thirdPartyInsuranceHasContact'] = false;    // NEU
        // dynamische Fragen werden bei Schritt 5 geladen
    }

    // NEU: eigener Updater für Kontaktpflicht
    public function updatedThirdPartyInsuranceHasContact()
    {
        $this->answers['thirdPartyInsuranceHasContact'] = $this->thirdPartyInsuranceHasContact;
    }

    public function updatedInsuranceId()
    {
        if (is_array($this->insuranceId)) {
            $this->insuranceId = $this->insuranceId['value'];
        }

        $this->insurance = Insurance::find($this->insuranceId);
        $this->answers['insuranceId'] = $this->insurance?->id;
    }

    public function updatedRegulationType()
    {
        $this->answers['regulationType'] = $this->regulationType;

        if ($this->regulationType === 'austehend') {
            $this->is_closed = false;
            $this->resetDates();
        } else {
            $this->is_closed = true;
        }

        $this->answers['is_closed']       = $this->is_closed;
        $this->regulationDetail           = null;
        $this->answers['regulationDetail'] = null;
    }

    public function updatedSelectedDates()
    {
        $dates = explode(' bis ', $this->selectedDates);
        if (count($dates) >= 2) {
            $this->started_at = \Carbon\Carbon::parse($dates[0])->format('d.m.Y');
            $this->ended_at   = \Carbon\Carbon::parse($dates[1])->format('d.m.Y');
        } else {
            $this->started_at = $this->selectedDates;
        }
    }

    public function resetDates()
    {
        $this->started_at   = null;
        $this->ended_at     = null;
        $this->selectedDates = null;
    }

    public function loadQuestions()
    {
        $this->variableQuestions = $this->insuranceSubType
            ? $this->insuranceSubType
                ->ratingQuestions()
                ->orderBy('insurance_subtype_rating_question.order_column')
                ->get()
                ->filter(function ($question) {
                    $vc = $question->visibility_condition ?? [];

                    if (!is_array($vc)) {
                        $vc = json_decode($vc, true) ?: [];
                    }

                    $isVisible = true;

                    // 1) Fremdversicherung
                    if (isset($vc['thirdPartyInsurance'])) {
                        if ((bool)$this->thirdPartyInsurance !== (bool)$vc['thirdPartyInsurance']) {
                            $isVisible = false;
                        }
                    }

                    // 2) Regulierungstyp Map (z. B. {"voll": true, "teil": true, "ablehnung": false})
                    if (isset($vc['regulationType']) && is_array($vc['regulationType']) && $this->regulationType !== null) {
                        $map     = $vc['regulationType'];
                        $current = $this->regulationType;
                        if (array_key_exists($current, $map) && (bool)$map[$current] === false) {
                            $isVisible = false;
                        }
                    }

                    // 3) Kontaktpflicht, wenn Fremdversicherung == true
                    if (isset($vc['thirdPartyInsuranceHasContactIsRequired'])) {
                        if ($this->thirdPartyInsurance === true && $this->thirdPartyInsuranceHasContact === false) {
                            $isVisible = false;
                        }
                    }

                    return $isVisible;
                })
                ->values()
            : collect();

        $this->questions   = collect($this->standardQuestions)->merge($this->variableQuestions);
        $this->totalSteps  = $this->questions->count();
    }

    public function nextStep()
    {
        $this->saveAnswers();

        if (count($this->questions) > 0) {
            if ($this->step < count($this->questions) + 1) {
                $this->step++;
            }
        } else {
            $this->step++;
        }

        // Wie im Kunden-Form: dynamische Fragen bei Schritt 5 laden
        if ($this->step == 5) {
            $this->loadQuestions();
        }
    }

    public function previousStep()
    {
        if ($this->step > 0) {
            $this->step--;
        }
    }

    public function saveAnswers()
    {
        $this->validate($this->rules());

        foreach ($this->questions as $question) {
            if (!isset($question->title)) {
                throw new \Exception("Frage hat keinen Titel: " . json_encode($question));
            }

            $key = (string)$question->title;

            if (!array_key_exists($key, $this->answers)) {
                $this->answers[$key] = null;
            }

            $value = $this->answers[$key];

            switch ($question->type) {
                case 'boolean':
                    $this->answers[$key] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;

                case 'number':
                    $this->answers[$key] = is_numeric($value) ? +$value : null;
                    break;

                case 'rating':
                    $this->answers[$key] = is_numeric($value) ? (int)$value : null;
                    break;

                case 'date':
                    if ($question->title === 'selectedDates') {
                        $this->answers['selectedDates'] = !empty($this->started_at)
                            ? [
                                'started_at' => $this->started_at,
                                'ended_at'   => $this->ended_at ?? null,
                              ]
                            : null;
                        break;
                    }
                    try {
                        $parsed = \Carbon\Carbon::parse($value);
                        $this->answers[$key] = $parsed->toDateString();
                    } catch (\Exception $e) {
                        $this->answers[$key] = null;
                    }
                    break;

                case 'select':
                    $this->answers[$key] = $value;
                    break;

                case 'textarea':
                    $this->answers[$key] = is_string($value) ? trim($value) : null;
                    break;

                case 'radio-textarea':
                    if ($key === 'regulationDetail') {
                        $this->answers[$key] = [
                            'selected_values' => $this->regulationDetail ?? null,
                            'textarea_value'  => isset($this->regulationComment) ? trim($this->regulationComment) : null,
                        ];
                    } elseif ($key === 'contractDetails') {
                        $this->answers[$key] = [
                            'contract_coverage_amount'   => $this->contractDetails['contract_coverage_amount'] ?? null,
                            'contract_deductible_amount' => $this->contractDetails['contract_deductible_amount'] ?? null,
                            'claim_amount'               => $this->contractDetails['claim_amount'] ?? null,
                            'claim_settlement_amount'    => $this->contractDetails['claim_settlement_amount'] ?? null,
                            'textarea_value'             => isset($this->contractDetails['textarea_value']) ? trim($this->contractDetails['textarea_value']) : null,
                        ];
                    }
                    break;

                case 'text':
                default:
                    $this->answers[$key] = is_string($value) ? trim($value) : null;
                    break;
            }
        }
    }

    public function submit()
    {
        $this->saveAnswers();

        $version = RatingQuestionnaireVersion::where('insurance_subtype_id', $this->insuranceSubTypeId)
            ->latest()
            ->first();

        ClaimRating::create([
            'user_id'                          => Auth::check() ? Auth::id() : null,
            'insurance_type_id'                => $this->insuranceTypeId,
            'insurance_subtype_id'             => $this->insuranceSubTypeId,
            'insurance_id'                     => $this->insurance?->id,
            'rating_questionnaire_versions_id' => optional($version)->id,
            'answers'                          => $this->answers,
            'status'                           => 'pending',
            'attachments' => [
                'scorings' => [
                    'regulation_speed'     => null,
                    'customer_service'     => null,
                    'fairness'             => null,
                    'transparency'         => null,
                    'overall_satisfaction' => null,
                    'questions'            => [],
                ],
                'eval_details' => [
                    'insuranceSubtype_average_rating_speed'           => null,
                    'insuranceSubtype_insurance_average_rating_speed' => null,
                ],
            ],
            'rating_score'       => null,
            'moderator_comment'  => null,
            'is_public'          => false,
            'verification_hash'  => Str::uuid(),
            'admin_review'       => [
                'is_anonymous'     => true,
                'created_by_admin' => true,
                'admin_id'         => Auth::id(),
                'source'           => 'admin-panel',
                'notes'            => '',
            ],
        ]);

        // Reset wie gehabt
        $this->reset([
            'insuranceTypeId','insuranceType',
            'insuranceSubTypeId','insuranceSubType',
            'insuranceId','insurance',
            'thirdPartyInsuranceAllowed','thirdPartyInsurance','thirdPartyInsuranceHasContact',
            'insuranceSubTypes','insurances',
            'is_closed','regulationType','regulationDetails','regulationComment',
            'contractDetails','selectedDates','started_at','ended_at',
            'step','answers','variableQuestions','questions',
        ]);

        $this->step      = 0;
        $this->questions = collect($this->standardQuestions);
        $this->answers   = array_fill_keys($this->questions->pluck('title')->toArray(), null);

        $this->showFormModal = false;
        $this->dispatch('anonymousReviewSaved');
    }

    public function rules()
    {
        $rules = [];
        if ($this->step >= 0) { $rules['insuranceTypeId'] = 'required'; }
        if ($this->step >= 1) { $rules['insuranceSubTypeId'] = 'required'; }
        if ($this->step >= 2) { $rules['insuranceId'] = 'required'; }
        if ($this->step >= 3) { $rules['regulationType'] = 'required'; }
        if ($this->step >= 4) {
            $rules['regulationDetails'] = 'required';
            if ($this->regulationDetails == 'Andere Gründe') {
                $rules['regulationComment'] = 'required';
            }
        }
        if ($this->step >= 5) {
            $rules['contractDetails.contract_deductible_amount'] = '';
            $rules['contractDetails.claim_amount'] = 'required|numeric';
            if ($this->regulationType == 'teilzahlung') {
                $rules['contractDetails.claim_settlement_amount'] = 'required|numeric';
            }
            $settlement = floatval($this->contractDetails['claim_settlement_amount'] ?? 0);
            $deductible = floatval($this->contractDetails['contract_deductible_amount'] ?? 0);
            $claim      = floatval($this->contractDetails['claim_amount'] ?? 0);
            if (($settlement + $deductible) < $claim
                && $this->regulationType != 'ablehnung'
                && $this->regulationType != 'austehend') {
                $rules['contractDetails.textarea_value'] = 'required';
            }
        }
        if ($this->step >= 6) {
            $rules['started_at'] = 'required|date|after_or_equal:setting_available_started_at|before_or_equal:today';
            if ($this->is_closed) {
                $rules['ended_at'] = 'required|date|after_or_equal:started_at|before_or_equal:today';
            }
        }
        if ($this->step >= 7) {
            foreach ($this->variableQuestions as $q) {
                $rules["answers.{$q->title}"] = '';
                if ($q->type == 'boolean')      { $rules["answers.{$q->title}"] .= 'boolean'; }
                elseif ($q->type == 'number')   { $rules["answers.{$q->title}"] .= 'numeric'; }
                elseif ($q->type == 'rating')   { $rules["answers.{$q->title}"] .= 'integer'; }
                elseif ($q->type == 'date')     { $rules["answers.{$q->title}"] .= ''; }
                elseif ($q->type == 'select')   { $rules["answers.{$q->title}"] .= 'string'; }
                elseif ($q->type == 'text')     { $rules["answers.{$q->title}"] .= 'string|max:255'; }

                if ($q->input_constraints) {
                    foreach ($q->input_constraints as $key => $value) {
                        if ($key == 'min') { $rules["answers.{$q->title}"] .= '|min:' . $value; }
                        elseif ($key == 'max') { $rules["answers.{$q->title}"] .= '|max:' . $value; }
                    }
                }
                if ($q->is_required) {
                    $rules["answers.{$q->title}"] .= '|required';
                }
            }
        }
        return $rules;
    }

    public function messages()
    {
        $messages = [
            'insuranceTypeId.required' => 'Bitte wähle den Versicherungstyp aus.',
            'insuranceSubTypeId.required' => 'Bitte wähle die Versicherungsart aus.',
            'insuranceId.required' => 'Bitte wähle die Versicherung aus.',
            'regulationType.required' => 'Bitte gib an, wie der Schaden reguliert wurde.',
            'regulationDetail.required' => 'Bitte gib Details zur Regulierung an.',
            'regulationComment.required' => 'Bitte gib einen Kommentar an, wenn du "Andere Gründe" ausgewählt hast.',
            'contractDetails.contract_deductible_amount.required' => 'Bitte gib deine Selbstbeteiligung an.',
            'contractDetails.claim_amount.required' => 'Bitte gib die Schadenshöhe an.',
            'contractDetails.claim_settlement_amount.required' => 'Bitte gib die Regulierungshöhe an, wenn der Fall abgeschlossen ist.',
            'contractDetails.textarea_value.required' => 'Bitte erkläre, warum nicht die volle Schadenshöhe abgedeckt wurde.',
            'started_at.required' => 'Bitte gib das Startdatum an.',
            'started_at.date' => 'Das Startdatum muss ein gültiges Datum sein.',
            'started_at.after_or_equal' => 'Das Startdatum muss nach oder gleich dem verfügbaren Startdatum liegen.',
            'started_at.before_or_equal' => 'Das Startdatum darf nicht in der Zukunft liegen.',
            'ended_at.required' => 'Bitte gib das Enddatum an.',
            'ended_at.date' => 'Das Enddatum muss ein gültiges Datum sein.',
            'ended_at.after_or_equal' => 'Das Enddatum muss nach oder gleich dem Startdatum liegen.',
            'ended_at.before_or_equal' => 'Das Enddatum darf nicht in der Zukunft liegen.',
        ];

        foreach ($this->variableQuestions as $q) {
            if ($q->is_required) {
                $messages["answers.{$q->title}.required"] = "Bitte beantworten Sie die Frage: {$q->question_text}";
            }
            if ($q->type == 'boolean') {
                $messages["answers.{$q->title}.boolean"] = "Die Antwort auf die Frage '{$q->question_text}' muss ein Wahrheitswert sein.";
            } elseif ($q->type == 'number') {
                $messages["answers.{$q->title}.numeric"] = "Die Antwort auf die Frage '{$q->question_text}' muss eine Zahl sein.";
            } elseif ($q->type == 'rating') {
                $messages["answers.{$q->title}.integer"] = "Das Rating ist bei dieser Frage '{$q->question_text}' Pflicht.";
                $messages["answers.{$q->title}.required"] = "Das Rating ist bei dieser Frage '{$q->question_text}' Pflicht.";
            } elseif ($q->type == 'text') {
                $messages["answers.{$q->title}.string"] = "Die Antwort auf die Frage '{$q->question_text}' muss ein Text sein.";
                $messages["answers.{$q->title}.max"]     = "Die Antwort auf die Frage '{$q->question_text}' darf maximal 255 Zeichen lang sein.";
            }
        }

        return $messages;
    }

    public function render()
    {
        return view('livewire.admin.reviews.anonymous-review-form');
    }
}
