import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';

import * as newsLayoutModule from '../resources/js/pagebuilder/templates/news-layout-01.js';

const {
    newsLayoutHtml,
    newsLayoutPreview,
    newsLayoutTemplate,
} = newsLayoutModule;

const readSource = (relativePath) => readFileSync(
    fileURLToPath(new URL(relativePath, import.meta.url)),
    'utf8'
);

const allowedFontAwesomeClasses = new Set([
    'fa-light',
    'fal',
    'fa-solid',
    'fas',
    'fa-regular',
    'far',
    'fa-clipboard-list',
    'fa-check',
    'fa-clock',
    'fa-folder-open',
    'fa-share-alt',
    'fa-lightbulb',
]);
const requiredContainerClasses = new Set(['container', 'mx-auto', 'px-3']);

assert.doesNotMatch(newsLayoutHtml, /\brc-news-/);
assert.doesNotMatch(newsLayoutHtml, /<style\b/i);
assert.match(newsLayoutHtml, /\sstyle="[^"]+"/);
assert.match(newsLayoutHtml, /data-template-version="2"/);
assert.match(newsLayoutHtml, /data-template-scope="content"/);
assert.match(newsLayoutHtml, /class="container mx-auto px-3"/);
assert.match(newsLayoutHtml, /data-news-container="true"/);
assert.doesNotMatch(newsLayoutHtml, /max-width\s*:/i);

const tagsWithClasses = [
    ...newsLayoutHtml.matchAll(/<([a-z][\w-]*)\b[^>]*\bclass="([^"]+)"[^>]*>/gi),
];

assert.ok(tagsWithClasses.length > 0, 'Font-Awesome icons must remain present.');

for (const [, tagName, classValue] of tagsWithClasses) {
    const classNames = classValue.trim().split(/\s+/);

    if (tagName.toLowerCase() === 'div') {
        assert.deepEqual(
            new Set(classNames),
            requiredContainerClasses,
            'The only layout classes must be exactly container mx-auto px-3.'
        );

        continue;
    }

    assert.equal(tagName.toLowerCase(), 'i', `Unexpected class on <${tagName}>.`);

    for (const className of classNames) {
        assert.ok(
            allowedFontAwesomeClasses.has(className),
            `Disallowed news template class: ${className}`
        );
    }
}

// Die drei Meta-Kacheln (Lesezeit, Kategorie, Teilen) liegen auf jeder
// Viewport-Breite nebeneinander - auch auf dem Handy.
const metaSection = newsLayoutHtml.match(/<section data-news-role="meta"[^>]*>/);

assert.ok(metaSection, 'The meta section must stay in the template.');
assert.match(
    metaSection[0],
    /grid-template-columns:repeat\(3,minmax\(0,1fr\)\)/,
    'The meta boxes must keep three columns on every viewport.'
);
assert.doesNotMatch(
    metaSection[0],
    /auto-fit|auto-fill/,
    'auto-fit collapses the meta boxes to a single column on mobile.'
);

// Das News-Layout darf ausschliesslich als Vorlage angeboten werden.
assert.equal(
    newsLayoutModule.addNewsDefaultLayoutBlock,
    undefined,
    'The news layout must no longer register a block.'
);

const pagebuilderSource = readSource('../resources/js/pagebuilder.js');

assert.doesNotMatch(
    pagebuilderSource,
    /addNewsDefaultLayoutBlock/,
    'The news layout must not be registered in the blocks panel.'
);
assert.match(pagebuilderSource, /appendNewsLayoutTemplate\(communityTemplates\)/);

// Die linke Sidebar (Blocks, Elements, Vorlagen) muss ein-/ausklappbar sein.
assert.match(pagebuilderSource, /studio:sidebarLeft:toggle/);
assert.match(pagebuilderSource, /sidebarLeftToggleState/);
assert.match(pagebuilderSource, /id: 'sidebar-left-toggle'/);

// Beide Sidebars starten eingeklappt, die rechte folgt der Auswahl und ein
// Klick ausserhalb schliesst die linke wieder.
assert.match(pagebuilderSource, /setupEditorChrome\(editor\)/);
assert.match(
    pagebuilderSource,
    /setSidebarLeftOpen\(editor, false\)/,
    'The left sidebar must start collapsed.'
);
assert.match(
    pagebuilderSource,
    /\[SIDEBAR_LEFT_EVENT\]: \(\{ fromEvent, setState \}\)/,
    'The toggle button must follow sidebar changes it did not cause.'
);
assert.match(
    pagebuilderSource,
    /runCommand\(SIDEBAR_RIGHT_SET, \{ visible: false \}\)/,
    'The right sidebar must start collapsed.'
);
assert.match(
    pagebuilderSource,
    /editor\.on\('component:toggled', syncRightSidebar\)/,
    'The right sidebar must follow the canvas selection.'
);
assert.match(
    pagebuilderSource,
    /visible: editor\.getSelectedAll\(\)\.length > 0/,
    'The right sidebar must be hidden while nothing is selected.'
);
assert.match(
    pagebuilderSource,
    /addEventListener\('pointerdown', closeLeftSidebarOnOutsideClick, true\)/,
    'Clicks outside the left sidebar must collapse it.'
);
assert.match(
    pagebuilderSource,
    /canvas:frame:load/,
    'Canvas clicks live in an iframe and need their own listener.'
);
assert.match(pagebuilderSource, /className: SIDEBAR_LEFT_CLASS/);
assert.match(pagebuilderSource, /className: SIDEBAR_RIGHT_CLASS/);
assert.match(pagebuilderSource, /className: SIDEBAR_LEFT_TOGGLE_CLASS/);

// Der Aufraeumpfad muss existieren, sonst haengen Listener am zerstoerten Editor.
assert.match(pagebuilderSource, /releaseEditorChrome\?\.\(\);\s*\n\s*window\.editor\.destroy\(\)/);

// Farben gehoeren in customTheme, nicht in die Layout-Zeile.
assert.match(pagebuilderSource, /customTheme: editorTheme/);
assert.doesNotMatch(
    pagebuilderSource,
    /type: 'row',[\s\S]{0,80}colors: \{/,
    'Layout rows ignore `colors`; the SDK only reads `customTheme`.'
);

// Das eigene Chrome-Stylesheet muss nach den SDK-Styles geladen werden.
const sdkStyleIndex = pagebuilderSource.indexOf("@grapesjs/studio-sdk/style");
const ownStyleIndex = pagebuilderSource.indexOf("../css/pagebuilder-ui.css");
assert.ok(ownStyleIndex > sdkStyleIndex && sdkStyleIndex > -1, 'Own chrome CSS must be imported after the SDK styles.');

const chromeCss = readSource('../resources/css/pagebuilder-ui.css');
assert.match(chromeCss, /\.rc-sidebar-left\b/);
assert.match(chromeCss, /\.rc-sidebar-right\b/);
assert.match(chromeCss, /prefers-reduced-motion/);

assert.match(newsLayoutPreview, /^data:image\/svg\+xml/);
assert.equal(newsLayoutTemplate.data.pages[0].component, newsLayoutHtml);
assert.match(newsLayoutTemplate.media, /^data:image\/svg\+xml/);

console.log(
    'News editor-style contract verified: shared container shell, '
    + 'three-column meta boxes on every viewport, template-only availability '
    + 'and a collapsible left sidebar.'
);
