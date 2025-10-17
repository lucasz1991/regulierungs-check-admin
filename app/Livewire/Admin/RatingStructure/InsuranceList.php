<?php

namespace App\Livewire\Admin\RatingStructure;

use Livewire\Component;
use App\Models\Insurance;
use Livewire\WithPagination;

class InsuranceList extends Component
{
    use WithPagination; 
    
    public $insurancesAll = [];
    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $perPage = 15;
    public $insurancesAllCount = 0;
    public string $ratingsFilter = 'all';

    protected $listeners = [
        'refreshInsurances' => 'loadInsurances',
        'orderInsurance' => 'handleOrderInsurance'
    ];


    public function analyzeAllInsuranceOnlineViaGpt(){
        $this->insurancesAll = Insurance::orderBy('order_column')->get();
        foreach ($this->insurancesAll as $insurance) {
            $insurance->analyzeInsuranceOnlineViaGpt();
        }
    }

    public function mount()
    {
        $this->insurancesAllCount = Insurance::count();
    }

    public function handleOrderInsurance($item, $position)
    {
        if (!isset($item, $position)) {
            return;
        }

        $movedInsurance = Insurance::find($item['id']);
        if (!$movedInsurance) {
            return;
        }

        $newPosition = (int) $position;

        $insurances = Insurance::orderBy('order_column')->get();
        $filteredInsurances = $insurances->reject(fn ($i) => $i->id == $movedInsurance->id)->values();

        $newOrder = collect();
        foreach ($filteredInsurances as $index => $insurance) {
            if ($index == $newPosition) {
                $newOrder->push($movedInsurance);
            }
            $newOrder->push($insurance);
        }

        if ($newPosition >= $filteredInsurances->count()) {
            $newOrder->push($movedInsurance);
        }

        foreach ($newOrder as $index => $insurance) {
            Insurance::where('id', $insurance->id)->update(['order_column' => $index]);
        }

        $this->resetPage();
    }

    public function deleteInsurance($id)
    {
        Insurance::findOrFail($id)->delete();
        $this->resetPage();
    }

    public function updatedRatingsFilter()
    {
        $this->resetPage();
    }



    public function toggleActive($id)
    {
        $insurance = Insurance::findOrFail($id);
        $insurance->update(['is_active' => !$insurance->is_active]);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $sortField = $this->sortField === 'ratings' ? 'claim_ratings_count' : $this->sortField;

        $insurances = Insurance::query()
            ->withCount(['claimRatings'])
            ->when($this->search, fn ($q) =>
                $q->where('name', 'like', '%'.$this->search.'%')
            )
            ->when($this->ratingsFilter === 'with', fn ($q) =>
                $q->has('claimRatings')
            )
            ->when($this->ratingsFilter === 'without', fn ($q) =>
                $q->doesntHave('claimRatings')
            )
            ->orderBy($sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.admin.rating-structure.insurance-list', [
            'insurances' => $insurances,
        ]);
    }


}
