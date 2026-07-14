import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';

import { newsLayoutHtml } from '../resources/js/pagebuilder/templates/news-layout-01.js';

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

console.log(
    `News Tailwind contract verified: ${customClassNames.length} custom classes, `
    + `${tailwindClassNames.length} scanned utility/icon classes, PageBuilder template glob active.`
);
