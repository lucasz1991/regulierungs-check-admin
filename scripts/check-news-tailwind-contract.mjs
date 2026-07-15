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
const requiredContainerClasses = new Set(['container', 'mx-auto', 'px-3']);

assert.doesNotMatch(newsLayoutHtml, /\brc-news-/);
assert.doesNotMatch(newsLayoutHtml, /<style\b/i);
assert.match(newsLayoutHtml, /\sstyle="[^"]+"/);
assert.match(newsLayoutHtml, /data-template-version="2"/);
assert.match(newsLayoutHtml, /data-template-scope="content"/);
assert.match(newsLayoutHtml, /class="container mx-auto px-3"/);
assert.match(newsLayoutHtml, /data-news-container="true"/);

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
    'The block preview must not insert a draggable image.'
);
assert.match(registeredBlock.config.media, /pointer-events:none/);
assert.match(newsLayoutPreview, /^data:image\/svg\+xml/);
assert.equal(newsLayoutTemplate.data.pages[0].component, newsLayoutHtml);

console.log(
    'News editor-style contract verified: shared container shell, '
    + 'no custom layout classes and no embedded style block.'
);
