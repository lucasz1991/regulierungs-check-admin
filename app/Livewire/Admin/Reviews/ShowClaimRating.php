<?php

namespace App\Livewire\Admin\Reviews;

use Livewire\Component;
use App\Models\ClaimRating;

class ShowClaimRating extends Component
{
    public $ratingId;
    public $rating;

    public function mount($ratingId)
    {
        $this->ratingId = $ratingId;
        $this->rating = ClaimRating::with('user', 'insurance', 'insuranceSubtype')->findOrFail($ratingId);
    }

    public function render()
    {
        return view('livewire.admin.reviews.show-claim-rating')
            ->layout('layouts.master');
    }
}
