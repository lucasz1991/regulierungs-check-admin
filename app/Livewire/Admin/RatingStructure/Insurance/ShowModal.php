<?php

namespace App\Livewire\Admin\RatingStructure\Insurance;

use Livewire\Component;
use App\Models\Insurance;
use App\Models\DetailInsuranceRating;
use App\Models\ClaimRating;

class ShowModal extends Component
{
    public bool $showModal = false;

    public ?int $insuranceId = null;
    public ?Insurance $insurance = null;

    // Anzeige-Felder
    public string $name = '';
    public ?string $description = null;
    public ?string $helptext = null;
    public ?string $initials = null;
    public array $style = [
        'font_color'   => null,
        'border_color' => null,
        'bg_color'     => null,
    ];
    public ?string $logo = null;
    public bool $is_active = true;
    public array $assignedInsuranceTypes = [];

    public ?array $currentAnalysis = null;


    protected $listeners = [
        'open-insurance-show-modal' => 'open',
    ];

    public function open(int $insuranceId): void
    {
        $this->reset();               // alle public Props zurücksetzen
        $this->showModal   = true;
        $this->insuranceId = $insuranceId;

        $this->insurance = Insurance::with('insuranceTypes:id,name')->findOrFail($insuranceId);

        // Stammdaten
        $this->name        = $this->insurance->name ?? '';
        $this->description = $this->insurance->description;
        $this->helptext    = $this->insurance->helptext;
        $this->initials    = $this->insurance->initials;
        $this->style       = $this->insurance->style ?? $this->style;
        $this->logo        = $this->insurance->logo;
        $this->is_active   = (bool) $this->insurance->is_active;
        $this->assignedInsuranceTypes = $this->insurance->insuranceTypes
            ->map(fn($t) => ['id' => $t->id, 'name' => $t->name])
            ->values()
            ->toArray();

        // Lade Bewertungen
        $this->loadAnalyses();

    }

    // Lade-Helper austauschen
protected function loadAnalyses(): void
{
    $r = DetailInsuranceRating::with('insuranceSubtype:id,name')
        ->where('insurance_id', $this->insuranceId)
        ->latest('id')    // oder ->latest('created_at')
        ->first();

    $this->currentAnalysis = $r ? [
        'id'             => $r->id,
        'subtype'        => $r->insuranceSubtype?->name,
        'type'           => $r->type,
        'status'         => $r->status,
        'fairness'       => $r->fairness,
        'speed'          => $r->speed,
        'communication'  => $r->communication,
        'transparency'   => $r->transparency,
        'total_score'    => $r->total_score,
        'ai_comment'     => $r->ai_comment,
        'ai_tags'        => $r->ai_tags ?: [],
        'created_at'     => optional($r->created_at)->locale('de')->diffForHumans(),
        'updated_at'     => optional($r->updated_at)->locale('de')->diffForHumans(),
    ] : null;
}

    public function analyzeInsuranceOnlineViaGpt(): void
    {
        $this->insurance?->analyzeInsuranceOnlineViaGpt();
        $this->dispatch('toast', [
            'type'  => 'success',
            'title' => 'Analyse gestartet',
            'text'  => 'Die AI-Analyse wurde angestoßen.',
        ]);
    }

    public function insuranceAnalyzeClaimRatingsViaGpt(): void
    {
        if (!$this->insurance) {
            $this->dispatch('toast', [
                'type'  => 'error',
                'title' => 'Fehler',
                'text'  => 'Keine Versicherung geladen.',
            ]);
            return;
        }
        $this->insurance?->insuranceAnalyzeClaimRatingsViaGpt();
        $this->dispatch('toast', [
            'type'  => 'success',
            'title' => 'Analyse gestartet',
            'text'  => 'Die AI-Analyse wurde angestoßen.',
        ]);
    }


    /** optional: Helper für Status-Badge Klassen (für Blade nutzbar) */
    public function statusBadge(string $status = null): string
    {
        $status = strtolower((string) $status);
        return match ($status) {
            'ok', 'done', 'success'    => 'text-green-700 bg-green-50 border-green-200',
            'review', 'pending', 'new' => 'text-amber-800 bg-amber-50 border-amber-200',
            'failed', 'error'          => 'text-red-700 bg-red-50 border-red-200',
            default                    => 'text-gray-700 bg-gray-50 border-gray-200',
        };
    }

    public function render()
    {
        return view('livewire.admin.rating-structure.insurance.show-modal');
    }
}
