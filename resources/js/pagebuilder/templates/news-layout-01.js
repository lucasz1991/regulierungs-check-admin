const toSvgDataUri = (svg) => {
    return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(
        svg.replace(/\s+/g, ' ').trim()
    );
};

const newsLayoutCardPreview = [
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 720 460" role="img" aria-label="Vorschau des News-Inhaltslayouts">',
    '<rect width="720" height="460" rx="22" fill="#fff"/>',
    '<rect x="36" y="34" width="648" height="10" rx="5" fill="#1d2d3c" opacity=".88"/>',
    '<rect x="36" y="56" width="590" height="7" rx="3.5" fill="#51606d" opacity=".5"/>',
    '<rect x="36" y="74" width="620" height="7" rx="3.5" fill="#51606d" opacity=".5"/>',
    '<rect x="36" y="104" width="648" height="104" rx="14" fill="#eaf7f6"/>',
    '<circle cx="69" cy="137" r="17" fill="#087f86"/>',
    '<rect x="99" y="126" width="190" height="10" rx="5" fill="#087f86"/>',
    '<circle cx="68" cy="163" r="7" fill="#15959b"/>',
    '<rect x="91" y="159" width="500" height="7" rx="3.5" fill="#51606d" opacity=".48"/>',
    '<circle cx="68" cy="187" r="7" fill="#15959b"/>',
    '<rect x="91" y="183" width="458" height="7" rx="3.5" fill="#51606d" opacity=".48"/>',
    '<path d="M36 234H684M36 284H684" stroke="#e5eaee"/>',
    '<path d="M252 234V284M468 234V284" stroke="#e5eaee"/>',
    '<circle cx="95" cy="259" r="10" fill="none" stroke="#344554" stroke-width="3"/>',
    '<rect x="115" y="255" width="78" height="7" rx="3.5" fill="#52616f" opacity=".72"/>',
    '<rect x="319" y="250" width="20" height="18" rx="3" fill="none" stroke="#344554" stroke-width="3"/>',
    '<rect x="350" y="255" width="83" height="7" rx="3.5" fill="#52616f" opacity=".72"/>',
    '<circle cx="533" cy="259" r="4" fill="#344554"/>',
    '<circle cx="553" cy="248" r="4" fill="#344554"/>',
    '<circle cx="553" cy="270" r="4" fill="#344554"/>',
    '<path d="M537 257L549 250M537 261L549 268" stroke="#344554" stroke-width="2"/>',
    '<rect x="568" y="255" width="76" height="7" rx="3.5" fill="#52616f" opacity=".72"/>',
    '<rect x="36" y="313" width="230" height="11" rx="5.5" fill="#1d2d3c" opacity=".88"/>',
    '<rect x="36" y="338" width="620" height="7" rx="3.5" fill="#51606d" opacity=".46"/>',
    '<rect x="36" y="356" width="570" height="7" rx="3.5" fill="#51606d" opacity=".46"/>',
    '<rect x="36" y="385" width="648" height="52" rx="12" fill="#eef8f7"/>',
    '<circle cx="68" cy="411" r="15" fill="#087f86"/>',
    '<rect x="96" y="400" width="128" height="8" rx="4" fill="#087f86"/>',
    '<rect x="96" y="417" width="500" height="6" rx="3" fill="#51606d" opacity=".42"/>',
    '</svg>'
].join('');

export const newsLayoutPreview = toSvgDataUri(newsLayoutCardPreview);

export const newsLayoutHtml = [
    '<section class="rc-news-template rc-news-template--content" data-template="news-layout-01" data-template-version="2" data-template-scope="content">',
    '  <div class="rc-news-shell">',
    '    <section class="rc-news-intro">',
    '      <p>Der Bundesgerichtshof hat mit einem aktuellen Urteil die Rechte von Versicherungsnehmern erneut gestärkt. Versicherer müssen künftig transparenter kommunizieren und dürfen Leistungen nicht ohne klare Begründung ablehnen.</p>',
    '    </section>',
    '',
    '    <section class="rc-news-summary">',
    '      <h2><i class="fa-light fa-clipboard-list rc-news-summary__icon" aria-hidden="true"></i> Das Wichtigste in Kürze</h2>',
    '      <ul>',
    '        <li><i class="fa-solid fa-check rc-news-summary__check" aria-hidden="true"></i><span>BGH stärkt Transparenzpflicht der Versicherer.</span></li>',
    '        <li><i class="fa-solid fa-check rc-news-summary__check" aria-hidden="true"></i><span>Leistungsablehnungen müssen klar und nachvollziehbar begründet sein.</span></li>',
    '        <li><i class="fa-solid fa-check rc-news-summary__check" aria-hidden="true"></i><span>Das Urteil hat Auswirkungen auf viele laufende Schadensfälle.</span></li>',
    '      </ul>',
    '    </section>',
    '',
    '    <section class="rc-news-meta" aria-label="Artikelinformationen">',
    '      <span><i class="fa-regular fa-clock rc-news-meta__icon" aria-hidden="true"></i> 5 Min. Lesezeit</span>',
    '      <span><i class="fa-light fa-folder-open rc-news-meta__icon" aria-hidden="true"></i> Recht &amp; Urteile</span>',
    '      <a href="#" aria-label="Artikel teilen"><i class="fa-light fa-share-nodes rc-news-meta__icon" aria-hidden="true"></i> Artikel teilen</a>',
    '    </section>',
    '',
    '    <article class="rc-news-article">',
    '      <section>',
    '        <h2>1. Was ist passiert?</h2>',
    '        <p>Der Bundesgerichtshof (Az. IV ZR 123/24) hat entschieden, dass Versicherer ihre Entscheidungen im Schadenfall verständlich und nachvollziehbar begründen müssen. Pauschale Ablehnungen ohne konkrete Begründung sind damit unzulässig.</p>',
    '      </section>',
    '',
    '      <section>',
    '        <h2>2. Warum ist das Urteil wichtig?</h2>',
    '        <p>Viele Versicherungsnehmer berichten über unzureichende Begründungen bei Leistungsablehnungen. Das Gericht stellt klar: Versicherer müssen transparent kommunizieren – und das stärkt die Rechte der Verbraucher erheblich.</p>',
    '      </section>',
    '',
    '      <aside class="rc-news-note">',
    '        <i class="fa-light fa-lightbulb rc-news-note__icon" aria-hidden="true"></i>',
    '        <div>',
    '          <h3>Gut zu wissen</h3>',
    '          <p>Das Urteil betrifft nicht nur neue Fälle, sondern kann auch für bereits laufende Verfahren relevant sein.</p>',
    '        </div>',
    '      </aside>',
    '',
    '      <section>',
    '        <h2>3. Was bedeutet das für dich?</h2>',
    '        <p>Du kannst von deiner Versicherung künftig eine detailliertere Begründung verlangen. Das erhöht deine Chancen, eine Leistung doch noch zu erhalten – oder deine Ansprüche erfolgreich durchzusetzen.</p>',
    '      </section>',
    '    </article>',
    '  </div>',
    '</section>',
    '',
    '<style>',
    '.rc-news-template, .rc-news-template * { box-sizing: border-box; }',
    '.rc-news-template { --rc-ink: #142536; --rc-muted: #52616f; --rc-teal: #087f86; --rc-teal-soft: #eaf7f6; margin: 0; width: 100%; background: #fff; color: var(--rc-ink); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }',
    '.rc-news-template a { color: inherit; text-decoration: none; }',
    '.rc-news-shell { width: min(920px, calc(100% - 48px)); margin: 0 auto; }',
    '.rc-news-intro { padding: 34px 0 22px; }',
    '.rc-news-intro p { margin: 0; font-size: clamp(17px, 2vw, 20px); font-weight: 540; line-height: 1.6; }',
    '.rc-news-summary { margin: 0 0 24px; border: 1px solid #dcefed; border-radius: 14px; padding: 25px 28px; background: var(--rc-teal-soft); }',
    '.rc-news-summary h2 { display: flex; align-items: center; gap: 12px; margin: 0 0 18px; color: #176b70; font-size: 16px; font-weight: 800; letter-spacing: .045em; text-transform: uppercase; }',
    '.rc-news-summary__icon { display: inline-grid; width: 34px; height: 34px; place-items: center; border-radius: 50%; background: var(--rc-teal); color: #fff; font-size: 16px; }',
    '.rc-news-summary ul { display: grid; gap: 13px; margin: 0; padding: 0; list-style: none; }',
    '.rc-news-summary li { display: grid; grid-template-columns: 22px minmax(0, 1fr); align-items: start; gap: 14px; color: #334554; font-size: 14px; line-height: 1.55; }',
    '.rc-news-summary__check { display: grid; width: 22px; height: 22px; place-items: center; border-radius: 50%; background: #15959b; color: #fff; font-size: 11px; line-height: 22px; text-align: center; }',
    '.rc-news-meta { display: grid; grid-template-columns: repeat(3, 1fr); border-top: 1px solid #e5eaee; border-bottom: 1px solid #e5eaee; }',
    '.rc-news-meta > * { display: flex; min-height: 66px; align-items: center; justify-content: center; gap: 9px; padding: 10px 16px; color: #344554; font-size: 13px; font-weight: 600; }',
    '.rc-news-meta > * + * { border-left: 1px solid #e5eaee; }',
    '.rc-news-meta__icon { display: inline-grid; width: 25px; height: 25px; place-items: center; font-size: 20px; }',
    '.rc-news-meta a { transition: color .2s ease, background-color .2s ease; }',
    '.rc-news-meta a:hover { background: #f3faf9; color: var(--rc-teal); }',
    '.rc-news-article { padding: 36px 0 16px; }',
    '.rc-news-article > section { margin-bottom: 32px; }',
    '.rc-news-article h2 { margin: 0 0 9px; color: var(--rc-ink); font-size: clamp(23px, 3vw, 30px); font-weight: 720; line-height: 1.2; letter-spacing: -.022em; }',
    '.rc-news-article p { margin: 0; color: #354757; font-size: 16px; line-height: 1.7; }',
    '.rc-news-note { display: grid; grid-template-columns: 46px minmax(0, 1fr); gap: 17px; margin: 4px 0 32px; border-radius: 13px; padding: 21px 24px; background: #eef8f7; }',
    '.rc-news-note__icon { display: grid; width: 42px; height: 42px; place-items: center; border-radius: 50%; background: var(--rc-teal); color: #fff; font-size: 23px; }',
    '.rc-news-note h3 { margin: 1px 0 6px; color: #176b70; font-size: 14px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }',
    '.rc-news-note p { font-size: 14px; line-height: 1.55; }',
    '@media (max-width: 760px) {',
    '  .rc-news-shell { width: min(100% - 32px, 920px); }',
    '  .rc-news-intro { padding-top: 26px; }',
    '  .rc-news-summary { padding: 21px 18px; }',
    '  .rc-news-meta { grid-template-columns: 1fr; }',
    '  .rc-news-meta > * { min-height: 52px; justify-content: flex-start; }',
    '  .rc-news-meta > * + * { border-top: 1px solid #e5eaee; border-left: 0; }',
    '  .rc-news-article { padding-top: 28px; }',
    '  .rc-news-note { grid-template-columns: 38px minmax(0, 1fr); padding: 18px; }',
    '  .rc-news-note__icon { width: 36px; height: 36px; font-size: 19px; }',
    '}',
    '</style>'
].join('\n');

export const addNewsDefaultLayoutBlock = (editor) => {
    const blockManager = editor.Blocks;
    const blockId = 'news-default-layout';

    if (blockManager.get(blockId)) {
        return blockManager.get(blockId);
    }

    return blockManager.add(blockId, {
        label: 'News Default Layout',
        category: {
            id: 'News',
            label: 'News',
            open: true
        },
        media: '<img src="' + newsLayoutPreview + '" alt="Vorschau des News Default Layouts" style="display:block;width:100%;height:100%;object-fit:cover;">',
        content: newsLayoutHtml,
        select: true,
        resetId: true,
        attributes: {
            title: 'News Default Layout einfügen'
        }
    }, { at: 0 });
};

export const newsLayoutTemplate = {
    id: 'regulierungs-check-news-layout-01',
    name: 'News 01 · Editorial Inhalt',
    media: newsLayoutPreview,
    author: {
        name: 'Regulierungs-Check'
    },
    data: {
        assets: [],
        pages: [
            {
                name: 'News Inhalt',
                component: newsLayoutHtml
            }
        ],
        custom: {
            projectType: 'web',
            projectName: 'News 01 · Editorial Inhalt',
            templateId: 'regulierungs-check-news-layout-01'
        }
    }
};

export const appendNewsLayoutTemplate = (templates = []) => {
    return [
        ...templates.filter((template) => template.id !== newsLayoutTemplate.id),
        newsLayoutTemplate
    ];
};
