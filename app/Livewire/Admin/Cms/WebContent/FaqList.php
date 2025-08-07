<?php

namespace App\Livewire\Admin\Cms\WebContent;

use Livewire\Component;
use App\Models\WebContent;
use Illuminate\Support\Carbon;

class FaqList extends Component
{
    public $contents = [];
    public $newKey;
    public $newValue;
    public $newType = 'faq';
    public $faqModalOpen = false;
    public $editingId = null;

    public function mount()
    {
        $this->loadContents();
    }

    public function loadContents()
    {
        $this->contents = WebContent::where('type', 'faq')->get();
    }

    public function editContent($id)
    {
        $content = WebContent::findOrFail($id);
        $this->editingId = $id;
        $this->newKey = $content->key;
        $this->newValue = $content->value;
        $this->newType = $content->type;
        $this->faqModalOpen = true;
    }

    public function closeForm()
    {
        $this->reset(['newKey', 'newValue', 'newType', 'editingId']);
        $this->faqModalOpen = false;
        $this->loadContents();
    }


    public function updateOrCreate()
    {
        $this->validate([
            'newKey' => 'required|string|max:255|unique:web_contents,key,' . $this->editingId,
            'newValue' => 'required|string',
            'newType' => 'required|in:text,html,faq',
        ]);

        WebContent::updateOrCreate([
            'id' => $this->editingId,
        ], [
            'key' => $this->newKey,
            'value' => $this->newValue,
            'type' => $this->newType,
        ]);
        $this->faqModalOpen = false;
        $this->reset(['newKey', 'newValue', 'newType', 'editingId']);
        $this->loadContents();
        session()->flash('success', 'WebContent gespeichert!');
    }



    public function deleteContent($id)
    {
        WebContent::findOrFail($id)->delete();
        $this->loadContents();
        session()->flash('success', 'WebContent gelöscht!');
    }

    public function render()
    {
        return view('livewire.admin.cms.web-content.faq-list');
    }
}
