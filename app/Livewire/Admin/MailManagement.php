<?php

namespace App\Livewire\Admin;

use App\Livewire\Concerns\RequiresRbacPermission;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Mail;

class MailManagement extends Component
{
    use RequiresRbacPermission;
    use WithPagination;

    protected function requiredRbacPermission(): string
    {
        return 'mails.manage';
    }

    public $sortBy = 'id'; // Standard-Sortierfeld
    public $sortDirection = 'desc'; // Standard-Sortierreihenfolge

    /**
     * Sortiere die Mails nach dem ausgewählten Feld.
     */
    public function sortByField($field)
    {
        if (! in_array($field, ['id','created_at','status'], true)) { return; }
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Erneutes Senden einer Mail.
     */
    public function resendMail($id)
    {
        $count = Mail::findOrFail($id)->retryFailed();
        session()->flash('message', $count.' fehlgeschlagene Empfänger erneut eingeplant.');
    }

    /**
     * Render-Methode.
     */
    public function render()
    {
        $mails = Mail::orderBy(in_array($this->sortBy, ['id','created_at','status'], true) ? $this->sortBy : 'id', $this->sortDirection === 'asc' ? 'asc' : 'desc')
            ->paginate(10);

        return view('livewire.admin.mail-management', [
            'mails' => $mails,
        ])->layout('layouts.master');
    }
}
