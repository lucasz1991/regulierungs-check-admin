<?php

namespace App\Livewire\Admin\RatingStructure\InsuranceTypes;

use Livewire\Component;
use App\Models\InsuranceType;
use App\Models\InsuranceSubtype;
use App\Models\Insurance;
use App\Support\PivotSorter;
use Illuminate\Support\Str;

class InsuranceTypesCreateEdit extends Component
{
    public $insuranceTypeId;
    public $name;
    public $description;
    public $is_active = true;
    public $showModal = false;

    public $icon = null;
    public $icon_type = 'svg'; 

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
        $this->is_active = true; 

        if ($id) {
            $this->availableInsurances = Insurance::whereDoesntHave('insuranceTypes', function ($query) use ($id) {
                $query->where('insurance_type_id', $id);
            })->orderBy('name')->get();

            $this->availableInsuranceSubTypes = InsuranceSubtype::whereDoesntHave('insuranceTypes', function ($query) use ($id) {
                $query->where('insurance_type_id', $id);
            })->orderBy('name')->get();

            $type = InsuranceType::with(['insurances', 'insuranceSubtypes'])->findOrFail($id);

            $this->insuranceTypeId = $type->id;
            $this->name = $type->name;
            $this->description = $type->description;
            $this->is_active = $type->is_active;

            $this->icon = $type->icon_svg;
$this->icon_type = $type->icon_type ?? 'svg';

            $this->assignedInsurances = $type->insurances
                ->map(fn ($i) => ['id' => $i->id, 'name' => $i->name, 'order_column' => $i->pivot->order_column])
                ->values()
                ->toArray();

            $this->assignedInsuranceSubTypes = $type->insuranceSubtypes
                ->map(fn ($i) => ['id' => $i->id, 'name' => $i->name, 'order_id' => $i->pivot->order_id])
                ->values()
                ->toArray();
        } else {
            $this->icon = null;
            $this->availableInsurances = Insurance::orderBy('name')->get();
            $this->availableInsuranceSubTypes = InsuranceSubtype::orderBy('name')->get();
        }

        $this->showModal = true;
    }

    public function save()
    {
$validated = $this->validate([
    'name' => 'required|string|max:255',
    'description' => 'nullable|string|max:500',
    'is_active' => 'boolean',

    'icon_type' => 'required|in:svg,fontawesome',
    'icon' => 'nullable|string|max:20000',
]);

$iconValue = $this->icon_type === 'svg'
    ? $this->sanitizeSvg($this->icon)
    : (is_string($this->icon) ? trim($this->icon) : null);

$type = InsuranceType::updateOrCreate(
    ['id' => $this->insuranceTypeId],
    [
        'name' => $this->name,
        'slug' => Str::slug($this->name),
        'description' => $this->description,
        'is_active' => $this->is_active,

        'icon_type' => $this->icon_type,
        'icon_svg'  => $iconValue,
    ]
);

        $syncData = collect($this->assignedInsurances)
            ->mapWithKeys(fn ($item, $index) => [$item['id'] => ['order_column' => $index]])
            ->toArray();

        $type->insurances()->sync($syncData);

        $syncDataSubTypes = collect($this->assignedInsuranceSubTypes)
            ->mapWithKeys(fn ($item, $index) => [$item['id'] => ['order_id' => $index]])
            ->toArray();

        $type->insuranceSubtypes()->sync($syncDataSubTypes);

        $this->dispatch('refreshInsuranceTypes');
        $this->showModal = false;
    }

    protected function sanitizeSvg(?string $svg): ?string
    {
        $svg = is_string($svg) ? trim($svg) : null;
        if (!$svg) return null;

        // kill scripts + event handler
        $svg = preg_replace('#<\s*script[^>]*>.*?<\s*/\s*script\s*>#is', '', $svg);
        $svg = preg_replace('#on\w+\s*=\s*"[^"]*"#i', '', $svg);
        $svg = preg_replace("#on\w+\s*=\s*'[^']*'#i", '', $svg);

        // optional: nur SVG erlauben
        if (!str_contains(Str::lower($svg), '<svg')) {
            return null;
        }

        return $svg;
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
            ->reject(fn ($i) => $i['id'] == $id)
            ->values()
            ->toArray();
    }

    public function addInsuranceSubType()
    {
        if (!$this->insuranceSubTypeToAdd) return;

        $insuranceSubType = $this->availableInsuranceSubTypes->firstWhere('id', $this->insuranceSubTypeToAdd);
        if (!$insuranceSubType) return;

        if (!collect($this->assignedInsuranceSubTypes)->pluck('id')->contains($insuranceSubType->id)) {
            $this->assignedInsuranceSubTypes[] = [
                'id' => $insuranceSubType->id,
                'name' => $insuranceSubType->name,
                'order_id' => count($this->assignedInsuranceSubTypes),
            ];
        }

        $this->insuranceSubTypeToAdd = null;
    }

    public function removeInsuranceSubType($id)
    {
        $this->assignedInsuranceSubTypes = collect($this->assignedInsuranceSubTypes)
            ->reject(fn ($i) => $i['id'] == $id)
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
