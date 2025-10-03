<?php

namespace App\Livewire\Admin\RatingStructure\InsuranceSubtypes;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Http\Controllers\Admin\MediaController;
use App\Models\InsuranceSubtype;
use App\Models\InsuranceType;

class InsuranceSubtypesCreateEdit extends Component
{
    use WithFileUploads;

    public $showModal = false;
    public $insuranceSubtypeId;

    public $assignedInsuranceTypes = [];
    public $availableInsuranceTypes = [];
    public $insuranceTypeToAdd = null;

    public $name;
    public $description;
    public $is_active = true;
    public $allow_third_party = false;

    // JSON style inkl. logo_url
    public $style = [
        'font_color'   => null,
        'border_color' => null,
        'bg_color'     => null,
        'logo_url'     => null,
    ];

    // Bild-Handling wie bei Insurance
    public $logo_image_file = null; // kann temp Upload ODER bestehender String-Pfad sein (siehe open())
    public $current_logo    = null; // persistierter Pfad aus DB (style.logo_url)

    protected $listeners = ['open-insurance-subtype-form' => 'open','reorderAssignedInsuranceTypes'];

    protected $rules = [
        'name'              => 'required|string|max:255',
        'description'       => 'nullable|string|max:500',
        'is_active'         => 'boolean',
        'allow_third_party' => 'boolean',
        'style'                 => 'nullable|array',
        'style.font_color'      => 'nullable|max:255',
        'style.border_color'    => 'nullable|max:255',
        'style.bg_color'        => 'nullable|max:255',
        'style.logo_url'        => 'nullable|max:1024',
        'logo_image_file'       => 'nullable|image|max:1024',
    ];

    protected function defaultStyle(): array
    {
        return [
            'font_color'   => '#4e4e4e', 
            'border_color' => '#4e4e4e', 
            'bg_color'     => '#dddddd', 
            'logo_url'     => null,
        ];
    }

    public function open($id = null)
    {
        $this->resetValidation();
        $this->reset();

        $this->style = $this->defaultStyle();

        if ($id) {
            $this->insuranceSubtypeId = $id;
            $subtype = InsuranceSubtype::with('insuranceTypes')->findOrFail($id);

            $this->availableInsuranceTypes = InsuranceType::whereDoesntHave('insuranceSubtypes', function ($q) use ($id) {
                $q->where('insurance_subtype_id', $id);
            })->orderBy('name')->get();

            $this->assignedInsuranceTypes = $subtype->insuranceTypes
                ->map(fn($i) => ['id' => $i->id, 'name' => $i->name])
                ->values()->toArray();

            $this->name              = $subtype->name;
            $this->description       = $subtype->description;
            $this->is_active         = (bool) $subtype->is_active;
            $this->allow_third_party = (bool) $subtype->allow_third_party;

            $dbStyle = is_array($subtype->style) ? $subtype->style : [];
            $this->style = array_merge($this->defaultStyle(), $dbStyle);

            // wichtig: so wie bei Insurance
            $this->current_logo    = $this->style['logo_url'] ?? null;
            $this->logo_image_file = $this->current_logo; // damit der Vergleich in save() funktioniert
        } else {
            $this->availableInsuranceTypes = InsuranceType::orderBy('name')->get();
        }

        $this->showModal = true;
    }

    public function save()
    {
        $validated = $this->validate();

        // ===== Bild-Upload exakt wie bei Insurance =====
        if ($this->logo_image_file && $this->logo_image_file !== $this->current_logo) {
            // Neues Bild hochladen
            $newPath = $this->uploadImageViaMediaController($this->logo_image_file);

            // Altes Bild löschen (optional, aber meist gewünscht)
            if ($this->current_logo) {
                $this->deleteImageViaMediaController($this->current_logo);
            }

            $this->style['logo_url'] = $newPath;
            $this->current_logo      = $newPath;
            $this->logo_image_file   = $newPath;
        } else {
            // nichts geändert → alten Pfad beibehalten
            $this->style['logo_url'] = $this->current_logo;
        }
        // ===============================================

        $subtype = InsuranceSubtype::updateOrCreate(
            ['id' => $this->insuranceSubtypeId],
            [
                'name'              => $this->name,
                'slug'              => $this->name ? Str::slug($this->name) : null,
                'description'       => $this->description,
                'is_active'         => (bool) $this->is_active,
                'allow_third_party' => (bool) $this->allow_third_party,
                'style'             => $this->style, // JSON
            ]
        );

        // Pivot (optional – falls du hier ebenfalls Typen synchronisierst)
        $syncData = collect($this->assignedInsuranceTypes)->mapWithKeys(
            fn($item, $i) => [$item['id'] => ['order_id' => $i]]
        )->toArray();
        $subtype->insuranceTypes()->sync($syncData);

        $this->showModal = false;
        $this->dispatch('refreshInsuranceSubtypes');
    }

    public function removeLogoImage()
    {
        if ($this->current_logo) {
            $this->deleteImageViaMediaController($this->current_logo);
        }
        $this->current_logo = null;
        $this->logo_image_file = null;
        $this->style['logo_url'] = null;
    }

    protected function uploadImageViaMediaController($file)
    {
        // identisch zu deinem Insurance-Flow:
        $request = Request::create('/admin/media/upload', 'POST', [], [], ['file' => $file]);
        $controller = new MediaController();
        $response = $controller->store($request);

        if (method_exists($response, 'getData')) {
            return $response->getData(true)['path'] ?? '';
        }
        throw new \Exception('Upload fehlgeschlagen.');
    }

    protected function deleteImageViaMediaController(string $path): void
    {
        // typischer Destroy-Call – passe ggf. an deine MediaController-Signatur an
        $request = Request::create('/admin/media/delete', 'POST', ['path' => $path]);
        $controller = new MediaController();
        if (method_exists($controller, 'destroy')) {
            $controller->destroy($request);
        }
    }

    // … deine add/remove/reorder Methoden bleiben unverändert …

    public function render()
    {
        return view('livewire.admin.rating-structure.insurance-subtypes.insurance-subtypes-create-edit');
    }
}
