<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class FontAwesomePagebuilderContractTest extends TestCase
{
    public function test_font_awesome_component_and_basic_block_are_registered(): void
    {
        $root = dirname(__DIR__, 2);
        $component = file_get_contents($root.'/resources/js/pagebuilder/fontawesome-icon.js');
        $pagebuilder = file_get_contents($root.'/resources/js/pagebuilder.js');

        $this->assertStringContainsString("const COMPONENT_TYPE = 'fontawesome-icon'", $component);
        $this->assertStringContainsString("const BLOCK_ID = 'fontawesome-icon'", $component);
        $this->assertStringContainsString('Components.addType(COMPONENT_TYPE', $component);
        $this->assertStringContainsString("id: 'Basic'", $component);
        $this->assertStringContainsString("name: 'faStyle'", $component);
        $this->assertStringContainsString("type: PICKER_TRAIT_TYPE,\n                            name: 'faIcon'", $component);
        $this->assertStringNotContainsString("label: 'Eigene Icon-Klasse'", $component);
        $this->assertStringContainsString("const PICKER_TRAIT_TYPE = 'fontawesome-icon-picker'", $component);
        $this->assertStringContainsString('traits.addType(PICKER_TRAIT_TYPE', $component);
        $this->assertStringContainsString('traits.getType(PICKER_TRAIT_TYPE)', $component);
        $this->assertStringContainsString('eventCapture: []', $component);
        $this->assertStringContainsString("root.setAttribute('data-fontawesome-icon-picker'", $component);
        $this->assertStringContainsString("search.placeholder = 'Icon suchen", $component);
        $this->assertStringContainsString("grid.setAttribute('role', 'listbox')", $component);
        $this->assertStringContainsString("button.setAttribute('role', 'option')", $component);
        $this->assertStringContainsString("emptyState.textContent = 'Kein passendes Icon gefunden.'", $component);
        $this->assertStringContainsString('updateSelection', $component);
        $this->assertStringContainsString("{ id: 'fa-light', label: 'Light (Pro)' }", $component);
        $this->assertStringContainsString("{ id: 'fa-duotone', label: 'Duotone (Pro)' }", $component);
        $this->assertStringNotContainsString("{ id: 'fa-thin'", $component);
        $this->assertStringNotContainsString("{ id: 'fa-sharp", $component);
        $this->assertStringContainsString("{ id: 'fa-share-alt', label: 'Teilen' }", $component);
        $this->assertStringContainsString('<i class="fa-light fal fa-star"', $component);
        $this->assertStringContainsString("import addFontAwesomeIconBlock from './pagebuilder/fontawesome-icon'", $pagebuilder);
        $this->assertStringContainsString('addFontAwesomeIconBlock(editor)', $pagebuilder);
    }

    public function test_all_picker_icons_and_styles_exist_in_the_installed_font_awesome_css(): void
    {
        $root = dirname(__DIR__, 2);
        $component = file_get_contents($root.'/resources/js/pagebuilder/fontawesome-icon.js');
        $fontAwesomeCss = file_get_contents($root.'/public/adminresources/fontawesome6/css/all.min.css');

        preg_match('/const iconOptions = \[(.*?)\n\];/s', $component, $optionsMatch);
        preg_match_all("/\{ id: '(fa-[a-z0-9-]+)', label:/", $optionsMatch[1] ?? '', $iconMatches);

        $this->assertNotEmpty($iconMatches[1]);

        foreach (array_unique($iconMatches[1]) as $iconClass) {
            $this->assertStringContainsString(
                '.'.$iconClass.':before',
                $fontAwesomeCss,
                sprintf('Die Vorschauklasse %s fehlt im installierten Font-Awesome-Paket.', $iconClass)
            );
        }

        foreach (['fal', 'far', 'fas', 'fad', 'fab'] as $styleClass) {
            $this->assertMatchesRegularExpression(
                '/\\.'.preg_quote($styleClass, '/').'(?:,|\\{)/',
                $fontAwesomeCss
            );
        }
    }

    public function test_news_content_template_uses_editor_styles_and_the_shared_container(): void
    {
        $template = file_get_contents(
            dirname(__DIR__, 2).'/resources/js/pagebuilder/templates/news-layout-01.js'
        );

        $this->assertStringContainsString('data-template-version="2"', $template);
        $this->assertStringContainsString('data-template-scope="content"', $template);
        $this->assertStringContainsString('class="container mx-auto px-3"', $template);
        $this->assertStringContainsString('fa-light fal fa-clipboard-list', $template);
        $this->assertStringContainsString('fa-solid fas fa-check', $template);
        $this->assertStringContainsString('fa-regular far fa-clock', $template);
        $this->assertStringContainsString('fa-light fal fa-folder-open', $template);
        $this->assertStringContainsString('fa-light fal fa-share-alt', $template);
        $this->assertStringContainsString('fa-light fal fa-lightbulb', $template);
        $this->assertStringNotContainsString('rc-news-', $template);
        $this->assertStringNotContainsString('<style', $template);
        $this->assertStringContainsString('style="box-sizing:border-box', $template);
    }
}
