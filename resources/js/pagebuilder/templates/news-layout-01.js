const toSvgDataUri = (svg) => {
    return 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(
        svg.replace(/\s+/g, ' ').trim()
    );
};

const newsHeroIllustration = [
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 720" role="img" aria-labelledby="title description">',
    '<title id="title">Platzhalter für das Titelbild einer News</title>',
    '<desc id="description">Stilisierter Richterhammer auf einem dunklen Schreibtisch</desc>',
    '<defs>',
    '<linearGradient id="background" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#111827"/><stop offset=".48" stop-color="#3b2418"/><stop offset="1" stop-color="#111827"/></linearGradient>',
    '<linearGradient id="wood" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#f5cc73"/><stop offset=".28" stop-color="#a85f24"/><stop offset=".7" stop-color="#5c2f17"/><stop offset="1" stop-color="#d48a3a"/></linearGradient>',
    '<radialGradient id="glow"><stop stop-color="#f6c76b" stop-opacity=".44"/><stop offset="1" stop-color="#f6c76b" stop-opacity="0"/></radialGradient>',
    '<filter id="shadow" x="-30%" y="-30%" width="160%" height="160%"><feDropShadow dx="0" dy="22" stdDeviation="18" flood-color="#000" flood-opacity=".55"/></filter>',
    '</defs>',
    '<rect width="1200" height="720" fill="url(#background)"/>',
    '<circle cx="790" cy="230" r="360" fill="url(#glow)"/>',
    '<path d="M0 574C278 514 592 532 1200 468V720H0Z" fill="#090d14" opacity=".8"/>',
    '<ellipse cx="750" cy="560" rx="330" ry="70" fill="#030712" opacity=".58"/>',
    '<g filter="url(#shadow)" transform="translate(758 330) rotate(-38)">',
    '<rect x="-61" y="-245" width="122" height="400" rx="48" fill="url(#wood)"/>',
    '<rect x="-82" y="-222" width="164" height="42" rx="20" fill="#3d1f12"/>',
    '<rect x="-82" y="80" width="164" height="42" rx="20" fill="#3d1f12"/>',
    '<rect x="-235" y="-205" width="470" height="168" rx="48" fill="url(#wood)"/>',
    '<rect x="-258" y="-178" width="48" height="114" rx="20" fill="#2e170e"/>',
    '<rect x="210" y="-178" width="48" height="114" rx="20" fill="#2e170e"/>',
    '</g>',
    '<g opacity=".28"><path d="M100 110h320M100 148h240M100 186h290" stroke="#fff" stroke-width="8" stroke-linecap="round"/></g>',
    '</svg>'
].join('');

const newsLayoutCardPreview = [
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 720 460" role="img" aria-label="Vorschau des News-Layouts">',
    '<defs>',
    '<linearGradient id="hero" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#111827"/><stop offset=".58" stop-color="#6b3519"/><stop offset="1" stop-color="#111827"/></linearGradient>',
    '<linearGradient id="woodPreview" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#f1c466"/><stop offset=".45" stop-color="#9d5523"/><stop offset="1" stop-color="#4d2816"/></linearGradient>',
    '<filter id="cardShadow"><feDropShadow dx="0" dy="6" stdDeviation="8" flood-opacity=".28"/></filter>',
    '</defs>',
    '<rect width="720" height="460" rx="22" fill="#fff"/>',
    '<rect width="720" height="220" rx="22" fill="url(#hero)"/>',
    '<rect y="198" width="720" height="22" fill="url(#hero)"/>',
    '<g transform="translate(532 117) rotate(-35)" filter="url(#cardShadow)">',
    '<rect x="-20" y="-90" width="40" height="165" rx="16" fill="url(#woodPreview)"/>',
    '<rect x="-105" y="-82" width="210" height="66" rx="20" fill="url(#woodPreview)"/>',
    '</g>',
    '<rect x="35" y="42" width="88" height="24" rx="7" fill="#5b32a3"/>',
    '<text x="49" y="59" fill="#fff" font-family="Arial, sans-serif" font-size="12" font-weight="700">URTEIL</text>',
    '<text x="35" y="91" fill="#fff" font-family="Arial, sans-serif" font-size="13">16. Mai 2025</text>',
    '<text x="35" y="130" fill="#fff" font-family="Arial, sans-serif" font-size="25" font-weight="700">BGH stärkt Verbraucher</text>',
    '<text x="35" y="160" fill="#fff" font-family="Arial, sans-serif" font-size="25" font-weight="700">im Schadenfall</text>',
    '<rect x="36" y="246" width="648" height="9" rx="4.5" fill="#1d2d3c" opacity=".84"/>',
    '<rect x="36" y="267" width="588" height="7" rx="3.5" fill="#51606d" opacity=".5"/>',
    '<rect x="36" y="285" width="620" height="7" rx="3.5" fill="#51606d" opacity=".5"/>',
    '<rect x="36" y="317" width="648" height="74" rx="12" fill="#eaf7f6"/>',
    '<circle cx="68" cy="345" r="15" fill="#087f86"/>',
    '<rect x="96" y="333" width="183" height="9" rx="4.5" fill="#087f86"/>',
    '<rect x="96" y="353" width="500" height="6" rx="3" fill="#51606d" opacity=".45"/>',
    '<rect x="96" y="370" width="455" height="6" rx="3" fill="#51606d" opacity=".45"/>',
    '<rect x="36" y="416" width="205" height="8" rx="4" fill="#1d2d3c" opacity=".84"/>',
    '<rect x="36" y="436" width="520" height="6" rx="3" fill="#51606d" opacity=".42"/>',
    '</svg>'
].join('');

export const newsHeroPlaceholder = toSvgDataUri(newsHeroIllustration);
export const newsLayoutPreview = toSvgDataUri(newsLayoutCardPreview);

export const newsLayoutHtml = [
    '<main class="rc-news-template" data-template="news-layout-01">',
    '  <section class="rc-news-hero">',
    '    <img class="rc-news-hero__image" src="' + newsHeroPlaceholder + '" alt="Titelbild der News – bitte im Page Builder austauschen">',
    '    <div class="rc-news-hero__overlay"></div>',
    '    <div class="rc-news-hero__content">',
    '      <span class="rc-news-badge"><span aria-hidden="true">⚖</span> Urteil</span>',
    '      <time class="rc-news-hero__date" datetime="2025-05-16">16. Mai 2025</time>',
    '      <h1>BGH stärkt Verbraucher<br>im Schadenfall</h1>',
    '    </div>',
    '  </section>',
    '',
    '  <div class="rc-news-shell">',
    '    <section class="rc-news-intro">',
    '      <p>Der Bundesgerichtshof hat mit einem aktuellen Urteil die Rechte von Versicherungsnehmern erneut gestärkt. Versicherer müssen künftig transparenter kommunizieren und dürfen Leistungen nicht ohne klare Begründung ablehnen.</p>',
    '    </section>',
    '',
    '    <section class="rc-news-summary">',
    '      <h2><span class="rc-news-summary__icon" aria-hidden="true">▣</span> Das Wichtigste in Kürze</h2>',
    '      <ul>',
    '        <li>BGH stärkt Transparenzpflicht der Versicherer.</li>',
    '        <li>Leistungsablehnungen müssen klar und nachvollziehbar begründet sein.</li>',
    '        <li>Das Urteil hat Auswirkungen auf viele laufende Schadensfälle.</li>',
    '      </ul>',
    '    </section>',
    '',
    '    <section class="rc-news-meta" aria-label="Artikelinformationen">',
    '      <span><span class="rc-news-meta__icon" aria-hidden="true">◷</span> 5 Min. Lesezeit</span>',
    '      <span><span class="rc-news-meta__icon" aria-hidden="true">□</span> Recht &amp; Urteile</span>',
    '      <a href="#" aria-label="Artikel teilen"><span class="rc-news-meta__icon" aria-hidden="true">↗</span> Artikel teilen</a>',
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
    '        <span class="rc-news-note__icon" aria-hidden="true">☼</span>',
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
    '',
    '  <section class="rc-news-related">',
    '    <div class="rc-news-related__head">',
    '      <h2>Ähnliche Themen</h2>',
    '      <a href="#">Alle anzeigen <span aria-hidden="true">→</span></a>',
    '    </div>',
    '    <div class="rc-news-related__grid">',
    '      <a class="rc-news-related-card" href="#">',
    '        <div class="rc-news-related-card__visual rc-news-related-card__visual--guide"><span aria-hidden="true">✓</span></div>',
    '        <div><span class="rc-news-related-card__badge rc-news-related-card__badge--guide">Ratgeber</span><time datetime="2025-05-14">14. Mai 2025</time><h3>Was tun, wenn die Versicherung nicht zahlt?</h3></div>',
    '      </a>',
    '      <a class="rc-news-related-card" href="#">',
    '        <div class="rc-news-related-card__visual rc-news-related-card__visual--judgment"><span aria-hidden="true">⚖</span></div>',
    '        <div><span class="rc-news-related-card__badge rc-news-related-card__badge--judgment">Urteil</span><time datetime="2025-05-09">09. Mai 2025</time><h3>OLG-Urteil: Kürzungen bei Reparaturkosten unzulässig</h3></div>',
    '      </a>',
    '      <a class="rc-news-related-card" href="#">',
    '        <div class="rc-news-related-card__visual rc-news-related-card__visual--news"><span aria-hidden="true">↗</span></div>',
    '        <div><span class="rc-news-related-card__badge rc-news-related-card__badge--news">News</span><time datetime="2025-05-06">06. Mai 2025</time><h3>Neue BaFin-Daten zeigen: Beschwerden steigen</h3></div>',
    '      </a>',
    '    </div>',
    '  </section>',
    '</main>',
    '',
    '<style>',
    '.rc-news-template, .rc-news-template * { box-sizing: border-box; }',
    '.rc-news-template { --rc-ink: #142536; --rc-muted: #52616f; --rc-teal: #087f86; --rc-teal-soft: #eaf7f6; margin: 0; min-height: 100vh; width: 100%; overflow: hidden; background: #fff; color: var(--rc-ink); font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }',
    '.rc-news-template a { color: inherit; text-decoration: none; }',
    '.rc-news-hero { position: relative; min-height: 440px; overflow: hidden; background: #111827; }',
    '.rc-news-hero__image { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; }',
    '.rc-news-hero__overlay { position: absolute; inset: 0; background: linear-gradient(90deg, rgba(4, 10, 18, .92) 0%, rgba(4, 10, 18, .65) 45%, rgba(4, 10, 18, .08) 78%), linear-gradient(0deg, rgba(4, 10, 18, .36), transparent 55%); }',
    '.rc-news-hero__content { position: relative; z-index: 1; display: flex; min-height: 440px; width: min(920px, calc(100% - 48px)); margin: 0 auto; padding: 72px 0 54px; flex-direction: column; align-items: flex-start; justify-content: flex-end; color: #fff; }',
    '.rc-news-badge { display: inline-flex; align-items: center; gap: 8px; border-radius: 7px; padding: 7px 11px; background: #5b32a3; box-shadow: 0 7px 22px rgba(34, 18, 71, .32); font-size: 12px; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; }',
    '.rc-news-hero__date { margin-top: 19px; font-size: 15px; font-weight: 600; color: rgba(255,255,255,.9); }',
    '.rc-news-hero h1 { max-width: 690px; margin: 13px 0 0; font-size: clamp(34px, 5vw, 58px); font-weight: 750; line-height: 1.05; letter-spacing: -.035em; text-wrap: balance; }',
    '.rc-news-shell { width: min(920px, calc(100% - 48px)); margin: 0 auto; }',
    '.rc-news-intro { padding: 34px 0 22px; }',
    '.rc-news-intro p { margin: 0; font-size: clamp(17px, 2vw, 20px); font-weight: 540; line-height: 1.6; }',
    '.rc-news-summary { margin: 0 0 24px; border: 1px solid #dcefed; border-radius: 14px; padding: 25px 28px; background: var(--rc-teal-soft); }',
    '.rc-news-summary h2 { display: flex; align-items: center; gap: 12px; margin: 0 0 18px; color: #176b70; font-size: 16px; font-weight: 800; letter-spacing: .045em; text-transform: uppercase; }',
    '.rc-news-summary__icon { display: inline-grid; width: 34px; height: 34px; place-items: center; border-radius: 50%; background: var(--rc-teal); color: #fff; font-size: 16px; }',
    '.rc-news-summary ul { display: grid; gap: 13px; margin: 0; padding: 0; list-style: none; }',
    '.rc-news-summary li { position: relative; padding-left: 36px; color: #334554; font-size: 14px; line-height: 1.55; }',
    '.rc-news-summary li::before { position: absolute; top: 0; left: 0; display: grid; width: 22px; height: 22px; place-items: center; border-radius: 50%; background: #15959b; color: #fff; content: "✓"; font-size: 12px; font-weight: 800; }',
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
    '.rc-news-related { width: min(1120px, calc(100% - 48px)); margin: 0 auto; border-top: 1px solid #e5eaee; padding: 25px 0 54px; }',
    '.rc-news-related__head { display: flex; align-items: center; justify-content: space-between; gap: 20px; margin-bottom: 18px; }',
    '.rc-news-related__head h2 { margin: 0; color: #327e82; font-size: 14px; font-weight: 800; letter-spacing: .055em; text-transform: uppercase; }',
    '.rc-news-related__head > a { color: #327e82; font-size: 13px; font-weight: 700; }',
    '.rc-news-related__grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; }',
    '.rc-news-related-card { display: grid; min-width: 0; grid-template-columns: 104px minmax(0, 1fr); gap: 13px; overflow: hidden; border: 1px solid #e5eaee; border-radius: 12px; padding: 10px; background: #fff; box-shadow: 0 8px 24px rgba(25, 45, 63, .07); transition: transform .2s ease, box-shadow .2s ease; }',
    '.rc-news-related-card:hover { transform: translateY(-3px); box-shadow: 0 13px 30px rgba(25, 45, 63, .12); }',
    '.rc-news-related-card__visual { display: grid; min-height: 98px; place-items: center; overflow: hidden; border-radius: 9px; color: #fff; font-size: 33px; }',
    '.rc-news-related-card__visual--guide { background: linear-gradient(135deg, #0f6570, #55b9b7); }',
    '.rc-news-related-card__visual--judgment { background: linear-gradient(135deg, #31204f, #9d72d4); }',
    '.rc-news-related-card__visual--news { background: linear-gradient(135deg, #0d536a, #70c7d4); }',
    '.rc-news-related-card__badge { display: inline-flex; border-radius: 5px; padding: 4px 6px; color: #fff; font-size: 9px; font-weight: 800; letter-spacing: .05em; text-transform: uppercase; }',
    '.rc-news-related-card__badge--guide { background: #d88918; }',
    '.rc-news-related-card__badge--judgment { background: #5b32a3; }',
    '.rc-news-related-card__badge--news { background: #087f65; }',
    '.rc-news-related-card time { display: block; margin-top: 8px; color: #73808b; font-size: 10px; }',
    '.rc-news-related-card h3 { margin: 6px 0 0; color: #1d2d3c; font-size: 13px; font-weight: 720; line-height: 1.35; }',
    '@media (max-width: 760px) {',
    '  .rc-news-hero, .rc-news-hero__content { min-height: 390px; }',
    '  .rc-news-hero__overlay { background: linear-gradient(0deg, rgba(4, 10, 18, .9) 0%, rgba(4, 10, 18, .44) 65%, rgba(4, 10, 18, .12) 100%); }',
    '  .rc-news-hero__content, .rc-news-shell, .rc-news-related { width: min(100% - 32px, 920px); }',
    '  .rc-news-hero__content { padding: 62px 0 32px; }',
    '  .rc-news-hero h1 { font-size: clamp(31px, 10vw, 43px); }',
    '  .rc-news-intro { padding-top: 26px; }',
    '  .rc-news-summary { padding: 21px 18px; }',
    '  .rc-news-meta { grid-template-columns: 1fr; }',
    '  .rc-news-meta > * { min-height: 52px; justify-content: flex-start; }',
    '  .rc-news-meta > * + * { border-top: 1px solid #e5eaee; border-left: 0; }',
    '  .rc-news-article { padding-top: 28px; }',
    '  .rc-news-note { grid-template-columns: 38px minmax(0, 1fr); padding: 18px; }',
    '  .rc-news-note__icon { width: 36px; height: 36px; font-size: 19px; }',
    '  .rc-news-related__grid { grid-template-columns: 1fr; }',
    '  .rc-news-related-card { grid-template-columns: 92px minmax(0, 1fr); }',
    '  .rc-news-related-card__visual { min-height: 90px; }',
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
    name: 'News 01 · Editorial Detail',
    media: newsLayoutPreview,
    author: {
        name: 'Regulierungs-Check'
    },
    data: {
        assets: [
            {
                type: 'image',
                src: newsHeroPlaceholder,
                name: 'News Hero Platzhalter'
            }
        ],
        pages: [
            {
                name: 'News Detail',
                component: newsLayoutHtml
            }
        ],
        custom: {
            projectType: 'web',
            projectName: 'News 01 · Editorial Detail',
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
