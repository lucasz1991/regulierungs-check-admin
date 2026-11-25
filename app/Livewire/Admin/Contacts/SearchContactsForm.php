<?php

namespace App\Livewire\Admin\Contacts;

use App\Http\Controllers\Admin\ContactController;
use App\Livewire\Concerns\RequiresRbacPermission;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;

class SearchContactsForm extends Component
{
    use RequiresRbacPermission, WithFileUploads;

    public bool $showModal = false;

    public $file;

    #[Locked]
    public array $headers = [];

    public array $mapping = [];

    #[Locked]
    public array $rows = [];

    #[Locked]
    public array $htmlRows = [];

    public bool $updateExisting = false;

    protected function requiredRbacPermission(): string
    {
        return 'contacts.manage';
    }

    public function openModal(): void
    {
        $this->closeModal();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->reset('showModal', 'file', 'headers', 'mapping', 'rows', 'htmlRows', 'updateExisting');
        $this->resetValidation();
    }

    public function updatedFile(): void
    {
        $this->reset('headers', 'mapping', 'rows', 'htmlRows');
    }

    public function updatedMapping(): void
    {
        $this->rows = [];
    }

    public function analyzeFile(ContactController $parser): void
    {
        $this->validate(['file' => 'required|file|max:10240|extensions:csv,txt,html,htm']);
        $path = $this->file->getRealPath();
        $type = strtolower($this->file->getClientOriginalExtension());
        if ($type === 'csv') {
            [$headers, $rows] = $parser->csv($path);
            if ($headers !== $this->headers) {
                $this->headers = $headers;
                $this->mapping = $parser->columnMap($headers);
            }
            $mapped = array_filter($this->mapping);
            if (! in_array('name', $mapped, true) || count($mapped) !== count(array_unique($mapped)) || array_diff($mapped, array_keys(Contact::FIELDS))) {
                throw ValidationException::withMessages(['mapping' => 'Bitte Name und jede gewünschte Spalte genau einmal zuordnen.']);
            }
            $records = [];
            foreach ($rows as $row) {
                $fields = [];
                foreach ($mapped as $i => $field) {
                    $fields[$field] = $row[$i] ?? '';
                }
                $records[] = $fields;
            }
        } else {
            $this->htmlRows = $parser->html(file_get_contents($path));
            $records = $this->htmlRows;
        }
        $this->buildPreview($records);
    }

    public function loadDetails(ContactController $parser): void
    {
        $count = 0;
        foreach ($this->htmlRows as &$row) {
            if (empty($row['_detail']) || ! empty($row['email'])) {
                continue;
            }
            try {
                $row = array_replace($row, $parser->detail($row['_detail']));
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                $this->addError('file', 'Eine Detailseite war nicht erreichbar. Vorhandene Daten können importiert werden.');
            }
            $row['_detail'] = '';
            if (++$count === 4) {
                break;
            }
        }
        unset($row);
        $this->buildPreview($this->htmlRows);
    }

    private function buildPreview(array $records): void
    {
        $this->rows = [];
        $seen = [];
        foreach ($records as $i => $record) {
            $fields = Contact::clean($record);
            $check = Validator::make($fields, Contact::fieldRules(), [], Contact::FIELDS);
            $key = $fields['email'] ?: json_encode($fields, JSON_UNESCAPED_UNICODE);
            $error = $check->errors()->first() ?: (isset($seen[$key]) ? 'Doppelte Zeile in der Datei.' : '');
            $exists = ! $error && Contact::matchImported($fields)->exists();
            $this->rows[] = ['number' => $i + 1, 'fields' => $fields, 'error' => $error, 'exists' => $exists];
            if (! $error) {
                $seen[$key] = true;
            }
        }
        if (! $this->rows) {
            $this->addError('file', 'Keine Kontaktzeilen gefunden.');
        }
    }

    public function saveContacts(): void
    {
        if (! $this->rows) {
            return;
        }
        $count = DB::transaction(function () {
            $count = 0;
            foreach ($this->rows as $row) {
                if ($row['error']) {
                    continue;
                }
                $fields = array_filter($row['fields'], fn ($v) => $v !== null && $v !== '');
                $contact = Contact::matchImported($row['fields'])->firstOrCreate([], $fields);
                if (! $contact->wasRecentlyCreated) {
                    if (! $this->updateExisting) {
                        continue;
                    }
                    $contact->fill($fields)->save();
                }
                $count++;
            }

            return $count;
        });
        $this->closeModal();
        $this->dispatch('contactsSaved');
        $this->dispatch('showAlert', $count.' Kontakte gespeichert.', 'success');
    }

    public function render()
    {
        return view('livewire.admin.contacts.search-contacts-form');
    }
}
