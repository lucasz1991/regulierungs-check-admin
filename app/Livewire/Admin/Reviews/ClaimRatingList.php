<?php

namespace App\Livewire\Admin\Reviews;

use App\Livewire\Concerns\RequiresRbacPermission;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ClaimRating;
use App\Livewire\Concerns\ComposesCustomerMail;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ClaimRatingList extends Component
{
    use RequiresRbacPermission;
    use ComposesCustomerMail;
    use WithPagination;

    protected function requiredRbacPermission(): string
    {
        return 'reviews.manage';
    }

    public $search = '';
    public $sortBy = 'created_at';
    public $sortDirection = 'desc';

    public $selectedRatings = [];
    public $selectAll = false;
    public $hasRatings = false;

    public $hasActiveRating = false;



    protected $listeners = [
        'anonymousReviewSaved' => 'resetPage',
    ];

    public function updatingSearch()
    {   
        $this->resetPage();
        $this->selectedRatings = [];
        $this->selectAll = false;
    }

    public function sortByField($field)
    {
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function toggleSelectAll()
    {
        $this->selectAll = !$this->selectAll;

        if ($this->selectAll) {
            $this->selectedRatings = $this->filteredRatings()->pluck('id')->toArray();
        } else {
            $this->selectedRatings = [];
        }
    }

    public function toggleRatingSelection($ratingId)
    {
        if (in_array($ratingId, $this->selectedRatings)) {
            $this->selectedRatings = array_filter($this->selectedRatings, fn($id) => $id != $ratingId);
        } else {
            $this->selectedRatings[] = $ratingId;
        }
    }

    protected function selectedMailIds(?int $id): array
    {
        return $id ? [$id] : $this->selectedRatings;
    }

    protected function mailAudience(array $ids): Collection
    {
        return ClaimRating::query()->whereIntegerInRaw('id', $ids)->with('user:id,email')->get()->pluck('user')->filter()->values();
    }

    public function reanalyse( $ratingId ){
        $claimRating = ClaimRating::find($ratingId);
        $claimRating->reanalyse();
    }

    public function makePublic( $ratingId ){
        $claimRating = ClaimRating::find($ratingId);
        if($claimRating->user?->isAdmin()){
            $claimRating->is_public = true;
            $claimRating->save();
            $this->dispatch('showAlert', 'Die Bewertung wurde öffentlich gemacht.', 'success');
        } else {
            $this->dispatch('showAlert', 'Nur Admin-Bewertungen können öffentlich gemacht werden.', 'error');
        }
    }

    public function makePrivate( $ratingId ){
        $claimRating = ClaimRating::find($ratingId);
        if($claimRating->user?->isAdmin()){
            $claimRating->is_public = false;
            $claimRating->save();
            $this->dispatch('showAlert', 'Die Bewertung wurde privat gemacht.', 'success');
        } else {
            $this->dispatch('showAlert', 'Nur Admin-Bewertungen können privat gemacht werden.', 'error');
        }
    }

    public function deleteRating($ratingId)
    {
        $rating = ClaimRating::find($ratingId);

        if (! $rating) {
            $this->dispatch('showAlert', 'Bewertung wurde nicht gefunden.', 'error');
            return;
        }

        // Optional: Berechtigungscheck, z. B. nur Admin
        if (! auth()->user()?->isAdmin()) {
            $this->dispatch('showAlert', 'Du hast keine Berechtigung, diese Bewertung zu löschen.', 'error');
            return;
        }

        // Wenn das Model SoftDeletes nutzt (tut es bei dir), wird nur "soft" gelöscht
        $rating->delete();

        // Falls das Rating in der Auswahl war, dort auch entfernen
        $this->selectedRatings = array_filter(
            $this->selectedRatings,
            fn ($id) => $id != $ratingId
        );

        // Pagination ggf. zurücksetzen, damit man nicht auf einer leeren Seite landet
        $this->resetPage();

        $this->dispatch('showAlert', 'Bewertung wurde gelöscht.', 'success');
    }

    private function filteredRatings()
    {
        return ClaimRating::query()
            ->where(function ($query) {
                $query->whereHas('user', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('insurance', function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%');
                })
                ->orWhere('answers->Service-Kommentar', 'like', '%' . $this->search . '%')
                ->orWhere('attachments->scorings->ai_overall_comment', 'like', '%' . $this->search . '%');
            });
    }

    public function render()
    {
        $ratings = $this->filteredRatings()->with('insurance', 'user')
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(10);

        $this->hasRatings = $ratings->count() > 0;
$this->hasActiveRating = ClaimRating::whereIn('status', ['rating', 'pending'])->exists();


        return view('livewire.admin.reviews.claim-rating-list', [
            'ratings' => $ratings,
            'hasActiveRating' => $this->hasActiveRating,
        ])->layout('layouts.master');
    }
}
