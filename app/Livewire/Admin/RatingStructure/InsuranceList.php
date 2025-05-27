<?php

namespace App\Livewire\Admin\RatingStructure;

use Livewire\Component;
use App\Models\Insurance;
use Livewire\WithPagination;

class InsuranceList extends Component
{
    use WithPagination; 
    
    public $insurancesAll = [];

    protected $listeners = [
        'refreshInsurances' => 'loadInsurances',
        'orderInsurance' => 'handleOrderInsurance'
    ];


    public function analyzeAllInsuranceOnlineViaGpt(){
        $this->insurancesAll = Insurance::orderBy('order_column')->get();
        foreach ($this->insurancesAll as $insurance) {
            if ($insurance->style['bg_color'] != $insurance->color) {
                continue;
            }
            $insurance->analyzeInsuranceOnlineViaGpt();
        }
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



    public function toggleActive($id)
    {
        $insurance = Insurance::findOrFail($id);
        $insurance->update(['is_active' => !$insurance->is_active]);
    }

    public function render()
    {
        
        return view('livewire.admin.rating-structure.insurance-list', [
            'insurances' => Insurance::orderBy('order_column')->paginate(10),
        ]);
    }
}
