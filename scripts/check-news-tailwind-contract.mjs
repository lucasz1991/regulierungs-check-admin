import assert from 'node:assert/strict';

import {
    addNewsDefaultLayoutBlock,
    newsLayoutHtml,
    newsLayoutPreview,
    newsLayoutTemplate,
} from '../resources/js/pagebuilder/templates/news-layout-01.js';

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

assert.doesNotMatch(newsLayoutHtml, /\brc-news-/);
assert.doesNotMatch(newsLayoutHtml, /<style\b/i);
assert.match(newsLayoutHtml, /\sstyle="[^"]+"/);
assert.match(newsLayoutHtml, /data-template-version="2"/);
assert.match(newsLayoutHtml, /data-template-scope="content"/);

const tagsWithClasses = [
    ...newsLayoutHtml.matchAll(/<([a-z][\w-]*)\b[^>]*\bclass="([^"]+)"[^>]*>/gi),
];

assert.ok(tagsWithClasses.length > 0, 'Font-Awesome-Icons müssen erhalten bleiben.');

for (const [, tagName, classValue] of tagsWithClasses) {
    assert.equal(
        tagName.toLowerCase(),
        'i',
        `Nur Font-Awesome-Icons dürfen Klassen verwenden: <${tagName}>`
    );

    for (const className of classValue.trim().split(/\s+/)) {
        assert.ok(
            allowedFontAwesomeClasses.has(className),
            `Unerlaubte News-Template-Klasse: ${className}`
        );
    }
}

let registeredBlock;
const editorStub = {
    Blocks: {
        get: () => undefined,
        add: (id, config, options) => {
            registeredBlock = { id, config, options };
            return config;
        },
    },
};

addNewsDefaultLayoutBlock(editorStub);

assert.equal(registeredBlock.id, 'news-default-layout');
assert.equal(registeredBlock.config.content, newsLayoutHtml);
assert.equal(registeredBlock.options.at, 0);
assert.match(registeredBlock.config.media, /^<svg\b/);
assert.doesNotMatch(
    registeredBlock.config.media,
    /<img\b/i,
    'Die Blockvorschau darf kein ziehbares Bild enthalten.'
);
assert.match(registeredBlock.config.media, /pointer-events:none/);
assert.match(newsLayoutPreview, /^data:image\/svg\+xml/);
assert.equal(newsLayoutTemplate.data.pages[0].component, newsLayoutHtml);

console.log(
    `News editor-style contract verified: ${tagsWithClasses.length} Font-Awesome-Icons, `
    + 'keine eigenen oder Tailwind-Klassen, kein eingebetteter Style-Block.'
);
