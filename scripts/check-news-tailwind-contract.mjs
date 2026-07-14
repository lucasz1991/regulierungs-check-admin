import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

import {
    addNewsDefaultLayoutBlock,
    newsLayoutHtml,
    newsLayoutPreview,
    newsLayoutTemplate,
} from '../resources/js/pagebuilder/templates/news-layout-01.js';

const pageBuilderTemplateGlob = './resources/js/pagebuilder/templates/**/*.{js,ts,jsx,tsx}';
const tailwindConfigSource = await readFile(
    new URL('../tailwind.config.js', import.meta.url),
    'utf8'
);

assert.ok(
    tailwindConfigSource.includes(`'${pageBuilderTemplateGlob}'`)
        || tailwindConfigSource.includes(`"${pageBuilderTemplateGlob}"`),
    `Tailwind content is missing ${pageBuilderTemplateGlob}`
);

const classNames = [
    ...newsLayoutHtml.matchAll(/\bclass=["']([^"']+)["']/g),
].flatMap((match) => match[1].trim().split(/\s+/).filter(Boolean));

assert.ok(classNames.length > 0, 'The News layout must expose its CSS class contract.');

const customClassNames = [...new Set(
    classNames.filter((className) => className.startsWith('rc-news-'))
)];
const tailwindClassNames = [...new Set(
    classNames.filter((className) => !className.startsWith('rc-news-'))
)];
const safelistBody = tailwindConfigSource.match(/safelist\s*:\s*\[([\s\S]*?)\]\s*,?/)?.[1] ?? '';

assert.doesNotMatch(
    safelistBody,
    /rc-news-/,
    'rc-news custom CSS selectors must not be added to the Tailwind safelist.'
);

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
    'The draggable block preview must not contain a native draggable image.'
);
assert.match(registeredBlock.config.media, /pointer-events:none/);
assert.match(newsLayoutPreview, /^data:image\/svg\+xml/);
assert.equal(newsLayoutTemplate.data.pages[0].component, newsLayoutHtml);

console.log(
    `News Tailwind contract verified: ${customClassNames.length} custom classes, `
    + `${tailwindClassNames.length} scanned utility/icon classes, PageBuilder block insertion verified.`
);
