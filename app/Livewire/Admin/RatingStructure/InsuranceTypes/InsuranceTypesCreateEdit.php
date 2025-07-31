<?php

namespace App\Livewire\Admin\RatingStructure\InsuranceTypes;

use Livewire\Component;
use App\Models\InsuranceType;
use App\Models\InsuranceSubtype;
use App\Models\Insurance;
use Illuminate\Support\Facades\Log;
use App\Support\PivotSorter;


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
    public $availableInsuranceSubTypes = [];
    public $assignedInsuranceSubTypes = [];
    public $insuranceSubTypeToAdd = null;

    protected $listeners = [
        'open-insurance-type-form' => 'open',
        'reorderAssignedInsuranceSubTypes' => 'handleReorderAssignedInsuranceSubTypes',
        'reorderAssignedInsurances' => 'handleReorderAssignedInsurances',
    ];

    public function open($id = null)
    {
        $this->reset();
        if ($id) {
            $this->showModal = true;
            $this->availableInsurances = Insurance::whereDoesntHave('insuranceTypes', function ($query) use ($id) {
                if ($id) {
                    $query->where('insurance_type_id', $id);
                }
            })->orderBy('name')->get();
            $this->availableInsuranceSubTypes = InsuranceSubtype::whereDoesntHave('insuranceTypes', function ($query) use ($id) {
                if ($id) {
                    $query->where('insurance_type_id', $id);
                }
            })->orderBy('name')->get();
            $type = InsuranceType::with('insurances')->findOrFail($id);
            $this->insuranceTypeId = $type->id;
            $this->name = $type->name;
            $this->description = $type->description;
            $this->is_active = $type->is_active;

            $this->assignedInsurances = $type->insurances
                ->map(fn($i) => ['id' => $i->id, 'name' => $i->name , 'order_column' => $i->pivot->order_column])
                ->values()
                ->toArray();
            $this->assignedInsuranceSubTypes = $type->insuranceSubtypes
                ->map(fn($i) => ['id' => $i->id, 'name' => $i->name , 'order_id' => $i->pivot->order_id])
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

        $syncDataSubTypes = collect($this->assignedInsuranceSubTypes)->mapWithKeys(function($item, $index) {
            return [$item['id'] => ['order_id' => $index]];
        })->toArray();
        $type->insuranceSubtypes()->sync($syncDataSubTypes);

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

    public function addInsuranceSubType()
    {
        if (!$this->insuranceSubTypeToAdd) return;

        $insuranceSubType = $this->availableInsuranceSubTypes->firstWhere('id', $this->insuranceSubTypeToAdd);
        if (!$insuranceSubType) return;

        if (!collect($this->assignedInsuranceSubTypes)->pluck('id')->contains($insuranceSubType->id)) {
            $this->assignedInsuranceSubTypes[] = ['id' => $insuranceSubType->id, 'name' => $insuranceSubType->name , 'order_id' => count($this->assignedInsuranceSubTypes)];
        }
        $this->insuranceSubTypeToAdd = null;
    }

    public function removeInsuranceSubType($id)
    {
        $this->assignedInsuranceSubTypes = collect($this->assignedInsuranceSubTypes)
            ->reject(fn($i) => $i['id'] == $id)
            ->values()
            ->toArray();
    }

    public function handleReorderAssignedInsurances($item, $position)
    {
        $model = InsuranceType::find($this->insuranceTypeId);
        if (!$model) return;

        PivotSorter::reorder(
            $model,
            'insurances',
            $item,
            $position,
            'order_column',
            $this->assignedInsurances
        );
    }

    public function handleReorderAssignedInsuranceSubTypes($item, $position)
    {
        $model = InsuranceType::find($this->insuranceTypeId);
        if (!$model) return;

        PivotSorter::reorder(
            $model,
            'insuranceSubtypes',
            $item,
            $position,
            'order_id',
            $this->assignedInsuranceSubTypes
        );
    }


    public function render()
    {
        return view('livewire.admin.rating-structure.insurance-types.insurance-types-create-edit');
    }
}
