const toSvgDataUri = (svg) => {
    return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(
        svg.replace(/\s+/g, ' ').trim()
    );
};

const newsLayoutCardPreview = [
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 720 460" role="img" aria-label="Vorschau des News-Inhaltslayouts" focusable="false" style="display:block;width:100%;height:auto;pointer-events:none">',
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
    '</svg>',
].join('');

export const newsLayoutPreview = toSvgDataUri(newsLayoutCardPreview);

// Die Vorlage nutzt bewusst keine Layout- oder Tailwind-Klassen. Alle
// Standardwerte liegen als vom Studio editierbare Inline-Styles vor. Einzige
// Klassen sind die notwendigen Font-Awesome-Klassen der Icon-Komponenten.
export const newsLayoutHtml = [
    '<section data-template="news-layout-01" data-template-version="2" data-template-scope="content" style="box-sizing:border-box;width:100%;margin:0;background:#ffffff;color:#142536;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,sans-serif">',
    '  <div data-news-role="content" style="box-sizing:border-box;width:min(920px,100%);margin:0 auto;padding:0 clamp(16px,3vw,24px)">',
    '    <section data-news-role="intro" style="padding:clamp(26px,4vw,36px) 0 clamp(20px,3vw,24px)">',
    '      <p style="margin:0;font-size:clamp(17px,2vw,20px);font-weight:540;line-height:1.6">Der Bundesgerichtshof hat mit einem aktuellen Urteil die Rechte von Versicherungsnehmern erneut gestärkt. Versicherer müssen künftig transparenter kommunizieren und dürfen Leistungen nicht ohne klare Begründung ablehnen.</p>',
    '    </section>',
    '',
    '    <section data-news-role="summary" style="box-sizing:border-box;margin:0 0 24px;border:1px solid #dcefed;border-radius:14px;padding:clamp(20px,3vw,28px);background:#eaf7f6">',
    '      <h2 style="display:flex;align-items:center;gap:12px;margin:0 0 18px;color:#176b70;font-size:16px;font-weight:800;letter-spacing:.045em;text-transform:uppercase">',
    '        <i class="fa-light fal fa-clipboard-list" aria-hidden="true" style="display:inline-grid;width:34px;height:34px;flex:0 0 34px;place-items:center;border-radius:9999px;background:#087f86;color:#ffffff;font-size:16px"></i>',
    '        <span>Das Wichtigste in Kürze</span>',
    '      </h2>',
    '      <ul style="display:grid;gap:13px;margin:0;padding:0;list-style:none">',
    '        <li style="display:grid;grid-template-columns:22px minmax(0,1fr);align-items:start;gap:14px;color:#334554;font-size:14px;line-height:1.55"><i class="fa-solid fas fa-check" aria-hidden="true" style="display:grid;width:22px;height:22px;place-items:center;border-radius:9999px;background:#15959b;color:#ffffff;font-size:11px;line-height:22px;text-align:center"></i><span>BGH stärkt Transparenzpflicht der Versicherer.</span></li>',
    '        <li style="display:grid;grid-template-columns:22px minmax(0,1fr);align-items:start;gap:14px;color:#334554;font-size:14px;line-height:1.55"><i class="fa-solid fas fa-check" aria-hidden="true" style="display:grid;width:22px;height:22px;place-items:center;border-radius:9999px;background:#15959b;color:#ffffff;font-size:11px;line-height:22px;text-align:center"></i><span>Leistungsablehnungen müssen klar und nachvollziehbar begründet sein.</span></li>',
    '        <li style="display:grid;grid-template-columns:22px minmax(0,1fr);align-items:start;gap:14px;color:#334554;font-size:14px;line-height:1.55"><i class="fa-solid fas fa-check" aria-hidden="true" style="display:grid;width:22px;height:22px;place-items:center;border-radius:9999px;background:#15959b;color:#ffffff;font-size:11px;line-height:22px;text-align:center"></i><span>Das Urteil hat Auswirkungen auf viele laufende Schadensfälle.</span></li>',
    '      </ul>',
    '    </section>',
    '',
    '    <section data-news-role="meta" aria-label="Artikelinformationen" style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,210px),1fr));overflow:hidden;border:1px solid #e5eaee;border-radius:10px">',
    '      <span style="display:flex;min-height:58px;align-items:center;justify-content:center;gap:9px;padding:10px 16px;color:#344554;font-size:13px;font-weight:600"><i class="fa-regular far fa-clock" aria-hidden="true" style="display:inline-grid;width:25px;height:25px;place-items:center;font-size:20px"></i>5 Min. Lesezeit</span>',
    '      <span style="display:flex;min-height:58px;align-items:center;justify-content:center;gap:9px;padding:10px 16px;border-left:1px solid #e5eaee;color:#344554;font-size:13px;font-weight:600"><i class="fa-light fal fa-folder-open" aria-hidden="true" style="display:inline-grid;width:25px;height:25px;place-items:center;font-size:20px"></i>Recht &amp; Urteile</span>',
    '      <a href="#" aria-label="Artikel teilen" style="display:flex;min-height:58px;align-items:center;justify-content:center;gap:9px;padding:10px 16px;border-left:1px solid #e5eaee;color:#344554;font-size:13px;font-weight:600;text-decoration:none"><i class="fa-light fal fa-share-alt" aria-hidden="true" style="display:inline-grid;width:25px;height:25px;place-items:center;font-size:20px"></i>Artikel teilen</a>',
    '    </section>',
    '',
    '    <article data-news-role="article" style="padding:clamp(28px,5vw,40px) 0 16px">',
    '      <section style="margin:0 0 32px">',
    '        <h2 style="margin:0 0 9px;color:#142536;font-size:clamp(23px,3vw,30px);font-weight:720;line-height:1.2;letter-spacing:-.022em">1. Was ist passiert?</h2>',
    '        <p style="margin:0;color:#354757;font-size:16px;line-height:1.7">Der Bundesgerichtshof (Az. IV ZR 123/24) hat entschieden, dass Versicherer ihre Entscheidungen im Schadenfall verständlich und nachvollziehbar begründen müssen. Pauschale Ablehnungen ohne konkrete Begründung sind damit unzulässig.</p>',
    '      </section>',
    '',
    '      <section style="margin:0 0 32px">',
    '        <h2 style="margin:0 0 9px;color:#142536;font-size:clamp(23px,3vw,30px);font-weight:720;line-height:1.2;letter-spacing:-.022em">2. Warum ist das Urteil wichtig?</h2>',
    '        <p style="margin:0;color:#354757;font-size:16px;line-height:1.7">Viele Versicherungsnehmer berichten über unzureichende Begründungen bei Leistungsablehnungen. Das Gericht stellt klar: Versicherer müssen transparent kommunizieren – und das stärkt die Rechte der Verbraucher erheblich.</p>',
    '      </section>',
    '',
    '      <aside data-news-role="note" style="display:grid;grid-template-columns:clamp(38px,6vw,46px) minmax(0,1fr);gap:17px;margin:4px 0 32px;border-radius:13px;padding:clamp(18px,3vw,24px);background:#eef8f7">',
    '        <i class="fa-light fal fa-lightbulb" aria-hidden="true" style="display:grid;width:clamp(36px,6vw,42px);height:clamp(36px,6vw,42px);place-items:center;border-radius:9999px;background:#087f86;color:#ffffff;font-size:clamp(19px,3vw,23px)"></i>',
    '        <div>',
    '          <h3 style="margin:1px 0 6px;color:#176b70;font-size:14px;font-weight:800;letter-spacing:.05em;text-transform:uppercase">Gut zu wissen</h3>',
    '          <p style="margin:0;color:#354757;font-size:14px;line-height:1.55">Das Urteil betrifft nicht nur neue Fälle, sondern kann auch für bereits laufende Verfahren relevant sein.</p>',
    '        </div>',
    '      </aside>',
    '',
    '      <section style="margin:0 0 32px">',
    '        <h2 style="margin:0 0 9px;color:#142536;font-size:clamp(23px,3vw,30px);font-weight:720;line-height:1.2;letter-spacing:-.022em">3. Was bedeutet das für dich?</h2>',
    '        <p style="margin:0;color:#354757;font-size:16px;line-height:1.7">Du kannst von deiner Versicherung künftig eine detailliertere Begründung verlangen. Das erhöht deine Chancen, eine Leistung doch noch zu erhalten – oder deine Ansprüche erfolgreich durchzusetzen.</p>',
    '      </section>',
    '    </article>',
    '  </div>',
    '</section>',
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
            open: true,
        },
        media: newsLayoutCardPreview,
        content: newsLayoutHtml,
        select: true,
        resetId: true,
        attributes: {
            title: 'News Default Layout einfügen',
        },
    }, { at: 0 });
};

export const newsLayoutTemplate = {
    id: 'regulierungs-check-news-layout-01',
    name: 'News 01 · Editorial Inhalt',
    media: newsLayoutPreview,
    author: {
        name: 'Regulierungs-Check',
    },
    data: {
        assets: [],
        pages: [
            {
                name: 'News Inhalt',
                component: newsLayoutHtml,
            },
        ],
        custom: {
            projectType: 'web',
            projectName: 'News 01 · Editorial Inhalt',
            templateId: 'regulierungs-check-news-layout-01',
        },
    },
};

export const appendNewsLayoutTemplate = (templates = []) => {
    return [
        ...templates.filter((template) => template.id !== newsLayoutTemplate.id),
        newsLayoutTemplate,
    ];
};
