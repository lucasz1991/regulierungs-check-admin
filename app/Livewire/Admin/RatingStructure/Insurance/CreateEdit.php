<?php

namespace App\Livewire\Admin\RatingStructure\Insurance;

use Livewire\Component;
use App\Models\Insurance;
use App\Models\InsuranceType;

class CreateEdit extends Component
{
    public $insurance;
    public $insuranceId;
    public $name;
    public $slug;
    public $description;
    public $initials;
    public $color;
    public $is_active = true;
    public $selectedTypes = [];
    public $showModal = false;

    protected $listeners = ['open-insurance-form' => 'open'];

    public function open($insuranceId = null)
    {
        $this->reset();
        $this->showModal = true;

        if ($insuranceId) {
            $this->insuranceId = $insuranceId;
            $this->insurance = Insurance::with('insuranceTypes')->findOrFail($insuranceId);

            $this->name = $this->insurance->name;
            $this->slug = $this->insurance->slug;
            $this->description = $this->insurance->description;
            $this->initials = $this->insurance->initials;
            $this->color = $this->insurance->color;
            $this->is_active = $this->insurance->is_active;
            $this->selectedTypes = $this->insurance->insuranceTypes->pluck('id')->toArray();
        }
    }

    public function save()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255',
            'description' => 'nullable|string',
            'initials' => 'nullable|string|max:10',
            'color' => 'nullable|string|max:7',
            'is_active' => 'boolean',
            'selectedTypes' => 'array',
        ]);

        $insurance = Insurance::updateOrCreate(
            ['id' => $this->insuranceId],
            [
                'name' => $this->name,
                'slug' => $this->slug,
                'description' => $this->description,
                'initials' => $this->initials,
                'color' => $this->color,
                'is_active' => $this->is_active,
            ]
        );

        $insurance->insuranceTypes()->sync($this->selectedTypes);

        $this->showModal = false;
        $this->dispatch('refreshInsurances');
    }

    public function render()
    {
        return view('livewire.admin.rating-structure.insurance.create-edit', [
            'allTypes' => InsuranceType::all(),
        ]);
    }
}