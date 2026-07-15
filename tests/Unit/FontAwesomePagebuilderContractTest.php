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
        $this->assertStringContainsString("name: 'faIconPreset'", $component);
        $this->assertStringContainsString("name: 'faIcon'", $component);
        $this->assertStringContainsString("const PICKER_TRAIT_TYPE = 'fontawesome-icon-picker'", $component);
        $this->assertStringContainsString('traitManager.addType(PICKER_TRAIT_TYPE', $component);
        $this->assertStringContainsString("root.setAttribute('data-fontawesome-icon-picker'", $component);
        $this->assertStringContainsString("search.placeholder = 'Icon suchen …'", $component);
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

    public function test_news_content_template_uses_font_awesome_icons_instead_of_glyph_placeholders(): void
    {
        $template = file_get_contents(
            dirname(__DIR__, 2).'/resources/js/pagebuilder/templates/news-layout-01.js'
        );

        $this->assertStringContainsString('data-template-version="2"', $template);
        $this->assertStringContainsString('data-template-scope="content"', $template);
        $this->assertStringContainsString('fa-light fal fa-clipboard-list', $template);
        $this->assertStringContainsString('fa-solid fas fa-check', $template);
        $this->assertStringContainsString('fa-regular far fa-clock', $template);
        $this->assertStringContainsString('fa-light fal fa-folder-open', $template);
        $this->assertStringContainsString('fa-light fal fa-share-alt', $template);
        $this->assertStringContainsString('fa-light fal fa-lightbulb', $template);
        $this->assertStringNotContainsString('rc-news-', $template);
        $this->assertStringNotContainsString('<style', $template);
        $this->assertStringContainsString('style="box-sizing:border-box', $template);
        $this->assertStringNotContainsString('>▣<', $template);
        $this->assertStringNotContainsString('>◷<', $template);
        $this->assertStringNotContainsString('>□<', $template);
        $this->assertStringNotContainsString('>↗<', $template);
        $this->assertStringNotContainsString('>☼<', $template);
    }
}
