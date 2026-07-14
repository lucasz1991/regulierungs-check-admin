<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PagebuilderBootstrapContractTest extends TestCase
{
    public function test_pagebuilder_bootstrap_waits_for_the_module_and_reports_its_state(): void
    {
        $app = file_get_contents(dirname(__DIR__, 2).'/resources/js/app.js');

        $this->assertStringContainsString('async function initializePagebuilder', $app);
        $this->assertStringContainsString('await loadPagebuilderModule', $app);
        $this->assertStringContainsString("dispatchPagebuilderEvent('ready'", $app);
        $this->assertStringContainsString("dispatchPagebuilderEvent('error'", $app);
        $this->assertStringContainsString(
            "document.addEventListener('livewire:navigated', initializePagebuilderFromDom)",
            $app
        );
    }

    public function test_editor_view_uses_event_driven_loading_and_retry_without_a_fixed_delay(): void
    {
        $view = file_get_contents(
            dirname(__DIR__, 2).'/resources/views/livewire/admin/cms/edit-project.blade.php'
        );

        $this->assertStringNotContainsString('setTimeout(() => {initGrapesJs();}, 300)', $view);
        $this->assertStringContainsString('@pagebuilder:ready.window', $view);
        $this->assertStringContainsString('@pagebuilder:error.window', $view);
        $this->assertStringContainsString("new CustomEvent('pagebuilder:retry')", $view);
    }

    public function test_pagebuilder_has_a_safe_root_guard_and_valid_canvas_stylesheets(): void
    {
        $pagebuilder = file_get_contents(dirname(__DIR__, 2).'/resources/js/pagebuilder.js');

        $this->assertStringContainsString("const editorElement = document.getElementById('studio-editor')", $pagebuilder);
        $this->assertStringContainsString('if (!editorElement)', $pagebuilder);
        $this->assertStringNotContainsString("document.getElementById('studio-editor').getAttribute", $pagebuilder);
        $this->assertStringContainsString("theme: 'light'", $pagebuilder);
        $this->assertStringContainsString('settingsMenu: {', $pagebuilder);
        $this->assertStringContainsString('theme: false', $pagebuilder);
        $this->assertStringNotContainsString('settingsMenu: false', $pagebuilder);
        $this->assertStringContainsString(
            "window.editor.runCommand(StudioCommands.setStateTheme, { theme: 'light' })",
            $pagebuilder
        );
        $this->assertStringContainsString("'/build/css/tailwind.min.css'", $pagebuilder);
        $this->assertStringContainsString("'/adminresources/fontawesome6/css/all.min.css'", $pagebuilder);
    }

    public function test_vite_build_uses_a_hashed_entry_without_copying_over_it(): void
    {
        $root = dirname(__DIR__, 2);
        $viteConfig = file_get_contents($root.'/vite.config.js');
        $verifyEmailView = file_get_contents($root.'/resources/views/auth/verify-email.blade.php');
        $twoFactorView = file_get_contents($root.'/resources/views/auth/two-factor-challenge.blade.php');

        $this->assertStringContainsString("entryFileNames: 'js/' + `[name]` + `-[hash].js`", $viteConfig);
        $this->assertStringContainsString("src: 'resources/js/pages'", $viteConfig);
        $this->assertStringNotContainsString("src: 'resources/js',", $viteConfig);
        $this->assertStringNotContainsString("build/js/app.js", $verifyEmailView);
        $this->assertStringNotContainsString("build/js/app.js", $twoFactorView);
    }
}
