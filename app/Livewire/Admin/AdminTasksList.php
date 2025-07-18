<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AdminTask;
use Illuminate\Support\Facades\Auth;



class AdminTasksList extends Component
{
    use WithPagination;

    public function assignToMe($taskId)
    {
        $task = AdminTask::findOrFail($taskId);
        if (!$task->assigned_to_user_id) {
            $task->assigned_to_user_id = Auth::id();
            $task->status = 1;
            $task->save();
            $this->resetPage();
            $this->dispatch('showAlert', 'Aufgabe erfolgreich übernommen.', 'success');
        }
    }

    public function markAsCompleted($taskId)
    {
        $task = AdminTask::findOrFail($taskId);
        
        if ($task->status == 1) {
            $task->status = 2; 
            $task->save();
            
            
            $this->resetPage();
            $this->dispatch('showAlert', 'Aufgabe erfolgreich abgeschlossen.', 'success');
        }
    }



    public function render()
    {
        $tasks = AdminTask::orderBy('status', 'asc')
        ->orderBy('created_at', 'desc')
        ->paginate(20);
        return view('livewire.admin.admin-tasks-list', [
            'tasks' => $tasks
        ])->layout('layouts.master');
    }
}
