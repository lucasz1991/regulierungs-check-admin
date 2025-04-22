<?php

namespace App\Livewire\Admin\RatingStructure\InsuranceTypes;

use Livewire\Component;
use App\Models\InsuranceType;
use App\Models\Insurance;

class InsuranceTypesCreateEdit extends Component
{
    public $insuranceTypeId;
    public $name;
    public $description;
    public $is_active = true;
    public $showModal = false;

    public $availableInsurances = [];
    public $assignedInsurances = [];
    public $insuranceToAdd = null;

    protected $listeners = ['open-insurance-type-form' => 'open'];

    public function open($id = null)
    {
        $this->reset();
        $this->showModal = true;
        $this->availableInsurances = Insurance::orderBy('name')->get();

        if ($id) {
            $type = InsuranceType::with('insurances')->findOrFail($id);
            $this->insuranceTypeId = $type->id;
            $this->name = $type->name;
            $this->description = $type->description;
            $this->is_active = $type->is_active;

            $this->assignedInsurances = $type->insurances
                ->map(fn($i) => ['id' => $i->id, 'name' => $i->name])
                ->values()
                ->toArray();
        }
    }

    public function save()
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $type = InsuranceType::updateOrCreate(
            ['id' => $this->insuranceTypeId],
            [
                'name' => $this->name,
                'description' => $this->description,
                'is_active' => $this->is_active,
            ]
        );

        $syncData = collect($this->assignedInsurances)->mapWithKeys(function($item, $index) {
            return [$item['id'] => ['order_column' => $index]];
        })->toArray();

        $type->insurances()->sync($syncData);

        $this->dispatch('refreshInsuranceTypes');
        $this->showModal = false;
    }

    public function addInsurance()
    {
        if (!$this->insuranceToAdd) return;

        $insurance = $this->availableInsurances->firstWhere('id', $this->insuranceToAdd);
        if (!$insurance) return;

        if (!collect($this->assignedInsurances)->pluck('id')->contains($insurance->id)) {
            $this->assignedInsurances[] = ['id' => $insurance->id, 'name' => $insurance->name];
        }
        $this->insuranceToAdd = null;
    }

    public function removeInsurance($id)
    {
        $this->assignedInsurances = collect($this->assignedInsurances)
            ->reject(fn($i) => $i['id'] == $id)
            ->values()
            ->toArray();
    }

    public function reorder($items)
    {
        $this->assignedInsurances = collect($items)->map(fn($i) => [
            'id' => $i['id'],
            'name' => $i['name']
        ])->toArray();
    }

    public function render()
    {
        return view('livewire.admin.rating-structure.insurance-types.insurance-types-create-edit');
    }
}
