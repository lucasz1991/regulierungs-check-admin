<?php

namespace App\Livewire\Admin\Config;

use App\Livewire\Concerns\RequiresRbacPermission;
use Livewire\Component;
use App\Models\Setting;

class GrapesJsSettings extends Component
{
    use RequiresRbacPermission;

    protected function requiredRbacPermission(): string
    {
        return 'settings.manage';
    }

    public string $apiKey = '';

    public function mount(): void
    {
        $this->apiKey     = Setting::getValue('grapesjs', 'api_key') ?? '';
    }

    public function save(): void
    {
        Setting::setValue('grapesjs', 'api_key', $this->apiKey);

        $this->dispatch('notify', type: 'success', message: 'GrapesJS API-Einstellungen gespeichert.');
    }

    public function render()
    {
        return view('livewire.admin.config.grapes-js-settings');
    }
}
