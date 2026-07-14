const COMPONENT_TYPE = 'fontawesome-icon';
const BLOCK_ID = 'fontawesome-icon';

const styleOptions = [
    { id: 'fa-thin', label: 'Thin (Pro)' },
    { id: 'fa-light', label: 'Light (Pro)' },
    { id: 'fa-regular', label: 'Regular' },
    { id: 'fa-solid', label: 'Solid' },
    { id: 'fa-duotone', label: 'Duotone (Pro)' },
    { id: 'fa-sharp fa-thin', label: 'Sharp Thin (Pro)' },
    { id: 'fa-sharp fa-light', label: 'Sharp Light (Pro)' },
    { id: 'fa-sharp fa-regular', label: 'Sharp Regular (Pro)' },
    { id: 'fa-sharp fa-solid', label: 'Sharp Solid (Pro)' },
    { id: 'fa-brands', label: 'Brands' },
];

const iconOptions = [
    { id: '', label: 'Eigene Icon-Klasse verwenden' },
    { id: 'fa-star', label: 'Stern' },
    { id: 'fa-check', label: 'Haken' },
    { id: 'fa-circle-check', label: 'Haken im Kreis' },
    { id: 'fa-xmark', label: 'Schließen' },
    { id: 'fa-circle-info', label: 'Information' },
    { id: 'fa-circle-exclamation', label: 'Hinweis' },
    { id: 'fa-triangle-exclamation', label: 'Warnung' },
    { id: 'fa-lightbulb', label: 'Glühbirne' },
    { id: 'fa-clipboard-list', label: 'Checkliste' },
    { id: 'fa-list-check', label: 'Aufgabenliste' },
    { id: 'fa-clock', label: 'Uhr' },
    { id: 'fa-calendar-days', label: 'Kalender' },
    { id: 'fa-folder-open', label: 'Ordner' },
    { id: 'fa-share-nodes', label: 'Teilen' },
    { id: 'fa-link', label: 'Link' },
    { id: 'fa-arrow-right', label: 'Pfeil rechts' },
    { id: 'fa-arrow-left', label: 'Pfeil links' },
    { id: 'fa-chevron-right', label: 'Chevron rechts' },
    { id: 'fa-chevron-left', label: 'Chevron links' },
    { id: 'fa-magnifying-glass', label: 'Suche' },
    { id: 'fa-user', label: 'Benutzer' },
    { id: 'fa-users', label: 'Benutzergruppe' },
    { id: 'fa-envelope', label: 'E-Mail' },
    { id: 'fa-phone', label: 'Telefon' },
    { id: 'fa-location-dot', label: 'Standort' },
    { id: 'fa-house', label: 'Haus' },
    { id: 'fa-building-shield', label: 'Versicherung' },
    { id: 'fa-scale-balanced', label: 'Recht' },
    { id: 'fa-gavel', label: 'Urteil' },
    { id: 'fa-car', label: 'Auto' },
    { id: 'fa-shield-halved', label: 'Schutz' },
    { id: 'fa-file-lines', label: 'Dokument' },
    { id: 'fa-download', label: 'Download' },
    { id: 'fa-upload', label: 'Upload' },
    { id: 'fa-pen', label: 'Bearbeiten' },
    { id: 'fa-trash', label: 'Löschen' },
    { id: 'fa-bars', label: 'Menü' },
    { id: 'fa-facebook', label: 'Facebook' },
    { id: 'fa-instagram', label: 'Instagram' },
    { id: 'fa-linkedin', label: 'LinkedIn' },
    { id: 'fa-x-twitter', label: 'X / Twitter' },
];

const styleClassNames = new Set(
    styleOptions.flatMap(({ id }) => id.split(/\s+/)).filter(Boolean)
);

const nonIconClassNames = new Set([
    ...styleClassNames,
    'fa',
    'fas',
    'far',
    'fal',
    'fat',
    'fad',
    'fab',
    'fa-classic',
    'fa-fw',
    'fa-inverse',
    'fa-border',
    'fa-pull-left',
    'fa-pull-right',
    'fa-spin',
    'fa-spin-pulse',
    'fa-pulse',
    'fa-beat',
    'fa-beat-fade',
    'fa-bounce',
    'fa-fade',
    'fa-flip',
    'fa-shake',
    'fa-xs',
    'fa-sm',
    'fa-lg',
    'fa-xl',
    'fa-2xl',
]);

const findIconClass = (classNames) => classNames.find((className) => {
    return className.startsWith('fa-')
        && !nonIconClassNames.has(className)
        && !/^fa-(?:rotate|flip|stack|\d+x)(?:-|$)/.test(className);
});

const findStyle = (classNames) => {
    const matched = styleOptions.find(({ id }) => {
        return id.split(/\s+/).every((className) => classNames.includes(className));
    });

    return matched?.id || 'fa-solid';
};

export default function addFontAwesomeIconBlock(editor) {
    const { Components, Blocks } = editor;

    if (!Components.getType(COMPONENT_TYPE)) {
        Components.addType(COMPONENT_TYPE, {
            isComponent: (element) => {
                if (element.tagName !== 'I') {
                    return false;
                }

                const classNames = [...element.classList];
                const faIcon = findIconClass(classNames);

                if (!faIcon) {
                    return false;
                }

                return {
                    type: COMPONENT_TYPE,
                    faStyle: findStyle(classNames),
                    faIcon,
                    faIconPreset: iconOptions.some(({ id }) => id === faIcon) ? faIcon : '',
                };
            },
            model: {
                defaults: {
                    tagName: 'i',
                    name: 'Font Awesome Icon',
                    droppable: false,
                    components: [],
                    faStyle: 'fa-solid',
                    faIcon: 'fa-star',
                    faIconPreset: 'fa-star',
                    attributes: {
                        'aria-hidden': 'true',
                    },
                    traits: [
                        {
                            type: 'select',
                            name: 'faStyle',
                            label: 'Font-Awesome-Stil',
                            changeProp: true,
                            options: styleOptions,
                        },
                        {
                            type: 'select',
                            name: 'faIconPreset',
                            label: 'Icon auswählen',
                            changeProp: true,
                            options: iconOptions,
                        },
                        {
                            type: 'text',
                            name: 'faIcon',
                            label: 'Icon-Klasse',
                            placeholder: 'fa-star',
                            changeProp: true,
                        },
                        {
                            type: 'text',
                            name: 'title',
                            label: 'Titel / Tooltip',
                        },
                    ],
                },
                init() {
                    const classNames = this.getClasses();
                    const initialStyle = this.get('faStyle') || findStyle(classNames);
                    const initialIcon = this.get('faIcon') || findIconClass(classNames) || 'fa-star';

                    this.set({
                        faStyle: initialStyle,
                        faIcon: initialIcon,
                        faIconPreset: iconOptions.some(({ id }) => id === initialIcon) ? initialIcon : '',
                    }, { silent: true });

                    this._managedFaStyle = initialStyle;
                    this._managedFaIcon = initialIcon;
                    this.on('change:faStyle change:faIcon', this.syncFontAwesomeClasses);
                    this.on('change:faIconPreset', this.applyIconPreset);
                    this.syncFontAwesomeClasses();
                },
                applyIconPreset() {
                    const preset = this.get('faIconPreset');

                    if (preset) {
                        this.set('faIcon', preset);
                    }
                },
                syncFontAwesomeClasses() {
                    const style = String(this.get('faStyle') || 'fa-solid').trim();
                    const icon = String(this.get('faIcon') || 'fa-star').trim().split(/\s+/)[0];
                    const managedClassNames = new Set([
                        ...styleClassNames,
                        ...String(this._managedFaStyle || '').split(/\s+/),
                        this._managedFaIcon,
                    ]);
                    const preservedClassNames = this.getClasses().filter((className) => {
                        return className && !managedClassNames.has(className);
                    });

                    this.setClass([
                        ...new Set([
                            ...preservedClassNames,
                            ...style.split(/\s+/).filter(Boolean),
                            icon,
                        ]),
                    ]);

                    this._managedFaStyle = style;
                    this._managedFaIcon = icon;
                },
            },
        });
    }

    if (Blocks.get(BLOCK_ID)) {
        return Blocks.get(BLOCK_ID);
    }

    return Blocks.add(BLOCK_ID, {
        label: 'Font Awesome Icon',
        category: {
            id: 'Basic',
            label: 'Basic',
            open: true,
        },
        media: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m12 2.4 2.9 5.9 6.5.9-4.7 4.6 1.1 6.5-5.8-3.1-5.8 3.1 1.1-6.5-4.7-4.6 6.5-.9L12 2.4Z"/></svg>',
        content: {
            type: COMPONENT_TYPE,
            faStyle: 'fa-solid',
            faIcon: 'fa-star',
            faIconPreset: 'fa-star',
        },
        select: true,
        attributes: {
            title: 'Font Awesome Icon einfügen',
        },
    }, { at: 0 });
}

export { BLOCK_ID, COMPONENT_TYPE, iconOptions, styleOptions };
