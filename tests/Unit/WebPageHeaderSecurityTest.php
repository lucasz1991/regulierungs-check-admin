<?php

namespace Tests\Unit;

use Tests\TestCase;

class WebPageHeaderSecurityTest extends TestCase
{
    public function test_webpage_editor_sanitizes_icons_before_storage_and_preview(): void
    {
        $component = file_get_contents(app_path('Livewire/Admin/Cms/Webpages/WebpagesList.php'));
        $view = file_get_contents(resource_path('views/livewire/admin/cms/webpages/webpages-list.blade.php'));

        $this->assertStringContainsString('SafeIconMarkup::svg($this->icon)', $component);
        $this->assertStringContainsString("'icon' => \$safeIcon", $component);
        $this->assertStringContainsString('Der Seitentitel darf kein HTML enthalten.', $component);
        $this->assertStringContainsString('SafeIconMarkup::svg($icon)', $view);
        $this->assertStringNotContainsString("{!! \$icon ?: '<span", $view);
        $this->assertStringContainsString('if ($isGlobalAdmin)', $component);
        $this->assertStringContainsString("'custom_js' => \$this->custom_js", $component);
        $this->assertStringContainsString('@if(auth()->user()?->isAdmin())', $view);
    }
}
