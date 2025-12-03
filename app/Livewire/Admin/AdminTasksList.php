<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\AdminTask;
use App\Models\ClaimRating;
use Illuminate\Support\Facades\Auth;

class AdminTasksList extends Component
{
    use WithPagination;

    /**
     * Aufgabe einem selbst zuweisen.
     */
    public function assignToMe(int $taskId): void
    {
        $task = AdminTask::findOrFail($taskId);

        if (! $task->assigned_to_user_id) {
            $task->assigned_to_user_id = Auth::id();
            $task->status = 1; // In Bearbeitung
            $task->save();

            $this->resetPage();
            $this->dispatch('showAlert', 'Aufgabe erfolgreich übernommen.', 'success');
        }
    }

    /**
     * Generische Aufgabe als erledigt markieren.
     * (Nicht für claim_verification – dafür gibt es approve/reject.)
     */
    public function markAsCompleted(int $taskId): void
    {
        $task = AdminTask::findOrFail($taskId);

        if ((int) $task->status === 1 && $task->type !== 'claim_verification') {
            $task->status = 2; // Abgeschlossen
            $task->save();

            $this->resetPage();
            $this->dispatch('showAlert', 'Aufgabe erfolgreich abgeschlossen.', 'success');
        }
    }

    /**
     * Claim-Verifikation: genehmigen.
     */
    public function approveClaimVerification(int $taskId): void
    {
        $task = AdminTask::with('related')->findOrFail($taskId);

        if ($task->type !== 'claim_verification') {
            return;
        }

        /** @var ClaimRating|null $claim */
        $claim = $task->related;

        if (! $claim instanceof ClaimRating) {
            return;
        }

        // Claim-Model kümmert sich um Status, is_public & verification['state']
        $claim->markVerificationApproved();

        // Optional: Admin in admin_review nachziehen
        $review = $claim->admin_review ?? [];
        $review['verification_last_action']     = 'approved';
        $review['verification_last_action_by']  = Auth::id();
        $review['verification_last_action_at']  = now()->toDateTimeString();
        $claim->admin_review = $review;
        $claim->save();

        // AdminTask abschließen
        $task->status = 2;
        $task->save();

        $this->resetPage();
        $this->dispatch('showAlert', 'Bewertung wurde erfolgreich verifiziert und veröffentlicht.', 'success');
    }

    /**
     * Claim-Verifikation: ablehnen.
     * (Hier ohne extra Reason-Eingabe – kann später z. B. mit Modal erweitert werden.)
     */
    public function rejectClaimVerification(int $taskId): void
    {
        $task = AdminTask::with('related')->findOrFail($taskId);

        if ($task->type !== 'claim_verification') {
            return;
        }

        /** @var ClaimRating|null $claim */
        $claim = $task->related;

        if (! $claim instanceof ClaimRating) {
            return;
        }

        // Claim-Model kümmert sich um Status, is_public & verification['state']
        $claim->markVerificationRejected();

        // Optional: Admin in admin_review nachziehen
        $review = $claim->admin_review ?? [];
        $review['verification_last_action']     = 'rejected';
        $review['verification_last_action_by']  = Auth::id();
        $review['verification_last_action_at']  = now()->toDateTimeString();
        $claim->admin_review = $review;
        $claim->save();

        // AdminTask abschließen
        $task->status = 2;
        $task->save();

        $this->resetPage();
        $this->dispatch('showAlert', 'Bewertung wurde abgelehnt.', 'success');
    }

    public function render()
    {
        $tasks = AdminTask::with([
                'assignedTo',
                'related', // bei claim_verification = ClaimRating
            ])
            ->orderBy('status', 'asc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('livewire.admin.admin-tasks-list', [
            'tasks' => $tasks,
        ])->layout('layouts.master');
    }
}
