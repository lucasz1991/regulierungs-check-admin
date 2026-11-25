<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\ComposesCustomerMail;
use App\Livewire\Concerns\RequiresRbacPermission;
use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class ManageContacts extends Component
{
    use ComposesCustomerMail, RequiresRbacPermission, WithPagination;

    public string $search = '';

    public string $category = '';

    public string $permission = '';

    public string $sortBy = 'name';

    public string $sortDirection = 'asc';

    public array $selected = [];

    public array $form = [];

    #[Locked]
    public ?int $contactId = null;

    public bool $showForm = false;

    protected $listeners = ['contactsSaved' => 'refreshContacts'];

    protected function requiredRbacPermission(): string
    {
        return 'contacts.manage';
    }

    protected function externalMailOnly(): bool
    {
        return true;
    }

    protected function selectedMailIds(?int $id): array
    {
        return $id ? [$id] : $this->selected;
    }

    protected function mailAudience(array $ids): Collection
    {
        return Contact::whereIntegerInRaw('id', $ids)->get(['id', 'email', 'mail_permission']);
    }

    private function filtered(): Builder
    {
        $query = Contact::query();
        if (trim($this->search) !== '') {
            $pattern = '%'.trim($this->search).'%';
            $query->where(fn ($q) => $q->where('name', 'like', $pattern)->orWhere('email', 'like', $pattern)->orWhere('company', 'like', $pattern));
        }

        return $query->when($this->category !== '', fn ($q) => $q->where('category', $this->category))
            ->when($this->permission !== '', fn ($q) => $q->where('mail_permission', $this->permission))
            ->orderBy(in_array($this->sortBy, ['name', 'company', 'email', 'created_at'], true) ? $this->sortBy : 'name',
                $this->sortDirection === 'desc' ? 'desc' : 'asc')->orderBy('id');
    }

    public function updated($key): void
    {
        if (in_array($key, ['search', 'category', 'permission'], true)) {
            $this->refreshContacts();
        }
    }

    public function refreshContacts(): void
    {
        $this->selected = [];
        $this->resetPage();
    }

    public function sortByField(string $field): void
    {
        if (in_array($field, ['name', 'company', 'email', 'created_at'], true)) {
            $this->sortDirection = $this->sortBy === $field && $this->sortDirection === 'asc' ? 'desc' : 'asc';
            $this->sortBy = $field;
            $this->resetPage();
        }
    }

    public function selectPage(): void
    {
        $ids = $this->filtered()->forPage($this->getPage(), 20)->pluck('id')->all();
        $this->selected = array_values(array_unique(array_merge($this->selected, $ids)));
    }

    public function selectResults(): void
    {
        $this->selected = $this->filtered()->pluck('id')->all();
    }

    public function create(): void
    {
        $this->resetValidation();
        $this->contactId = null;
        $this->form = ['mail_permission' => 'unknown'];
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->create();
        $contact = Contact::findOrFail($id);
        $this->contactId = $id;
        $this->form = $contact->only([...array_keys(Contact::FIELDS), 'mail_permission']);
    }

    public function save(): void
    {
        $fields = Contact::clean($this->form);
        $fields['mail_permission'] = $this->form['mail_permission'] ?? 'unknown';
        $rules = Contact::fieldRules();
        $unique = Rule::unique('contacts', 'email');
        if ($this->contactId) {
            $unique->ignore($this->contactId);
        }
        $rules['email'] = ['nullable', 'email:rfc', 'max:254', $unique];
        $rules['mail_permission'] = ['required', Rule::in(array_keys(Contact::PERMISSIONS))];
        $this->form = $fields;
        $this->validate(collect($rules)->mapWithKeys(fn ($rule, $key) => ['form.'.$key => $rule])->all());
        $contact = $this->contactId ? Contact::findOrFail($this->contactId) : new Contact;
        $contact->fill($fields)->save();
        $this->showForm = false;
        session()->flash('contactNotice', 'Kontakt gespeichert.');
    }

    public function delete(int $id): void
    {
        Contact::findOrFail($id)->delete();
        $this->refreshContacts();
        session()->flash('contactNotice', 'Kontakt gelöscht.');
    }

    public function render()
    {
        return view('livewire.admin.manage-contacts', [
            'contacts' => $this->filtered()->paginate(20),
            'categories' => Contact::whereNotNull('category')->where('category', '!=', '')->distinct()->orderBy('category')->pluck('category'),
        ])->layout('layouts.master');
    }
}
