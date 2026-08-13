<?php

namespace App\Livewire;


use App\Livewire\Concerns\RequiresRbacPermission;
use Livewire\Component;
use App\Models\Message;

class AdminMessageBox extends Component
{
    use RequiresRbacPermission;

    protected function requiredRbacPermission(): string
    {
        return 'messages.manage';
    }

    public $messages;
    public $selectedMessage;
    public $showMessageModal = false;

    protected $listeners = [
        'refreshComponent' => '$refresh',
    ];

    public function mount()
    {
        $this->messages = auth()->user()->receivedMessages;
        $this->dispatch('refreshComponent');
    }

    public function showMessage($messageId)
    {
        $this->selectedMessage = $this->messages->firstWhere('id', $messageId);
        $this->selectedMessage->update([
            'status' => 2, 
        ]);
        $this->selectedMessage->save();
        $this->showMessageModal = true;
        $this->dispatch('refreshComponent');
    }
    public function render()
    {   
        return view('livewire.admin-message-box')->layout('layouts.master');
    }
}
