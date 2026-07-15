const COMPONENT_TYPE = 'fontawesome-icon';
const BLOCK_ID = 'fontawesome-icon';
const PICKER_TRAIT_TYPE = 'fontawesome-icon-picker';

// Das lokal vorhandene Paket ist Font Awesome Pro 5.15.3. Nur tatsächlich
// enthaltene Stile werden angeboten, damit jede Vorschau sichtbar bleibt.
const styleOptions = [
    { id: 'fa-light', label: 'Light (Pro)' },
    { id: 'fa-regular', label: 'Regular' },
    { id: 'fa-solid', label: 'Solid' },
    { id: 'fa-duotone', label: 'Duotone (Pro)' },
    { id: 'fa-brands', label: 'Brands' },
];

const iconOptions = [
    { id: '', label: 'Eigene Icon-Klasse' },
    { id: 'fa-star', label: 'Stern' },
    { id: 'fa-check', label: 'Haken' },
    { id: 'fa-check-circle', label: 'Haken im Kreis' },
    { id: 'fa-times', label: 'Schließen' },
    { id: 'fa-info-circle', label: 'Information' },
    { id: 'fa-exclamation-circle', label: 'Hinweis' },
    { id: 'fa-exclamation-triangle', label: 'Warnung' },
    { id: 'fa-lightbulb', label: 'Glühbirne' },
    { id: 'fa-clipboard-list', label: 'Checkliste' },
    { id: 'fa-tasks', label: 'Aufgabenliste' },
    { id: 'fa-clock', label: 'Uhr' },
    { id: 'fa-calendar-alt', label: 'Kalender' },
    { id: 'fa-folder-open', label: 'Ordner' },
    { id: 'fa-share-alt', label: 'Teilen' },
    { id: 'fa-link', label: 'Link' },
    { id: 'fa-arrow-right', label: 'Pfeil rechts' },
    { id: 'fa-arrow-left', label: 'Pfeil links' },
    { id: 'fa-chevron-right', label: 'Chevron rechts' },
    { id: 'fa-chevron-left', label: 'Chevron links' },
    { id: 'fa-search', label: 'Suche' },
    { id: 'fa-user', label: 'Benutzer' },
    { id: 'fa-users', label: 'Benutzergruppe' },
    { id: 'fa-envelope', label: 'E-Mail' },
    { id: 'fa-phone', label: 'Telefon' },
    { id: 'fa-map-marker-alt', label: 'Standort' },
    { id: 'fa-home', label: 'Haus' },
    { id: 'fa-building', label: 'Gebäude' },
    { id: 'fa-balance-scale', label: 'Recht' },
    { id: 'fa-gavel', label: 'Urteil' },
    { id: 'fa-car', label: 'Auto' },
    { id: 'fa-shield-alt', label: 'Schutz' },
    { id: 'fa-file-alt', label: 'Dokument' },
    { id: 'fa-download', label: 'Download' },
    { id: 'fa-upload', label: 'Upload' },
    { id: 'fa-pen', label: 'Bearbeiten' },
    { id: 'fa-trash', label: 'Löschen' },
    { id: 'fa-bars', label: 'Menü' },
    { id: 'fa-facebook', label: 'Facebook', style: 'fa-brands' },
    { id: 'fa-instagram', label: 'Instagram', style: 'fa-brands' },
    { id: 'fa-linkedin', label: 'LinkedIn', style: 'fa-brands' },
    { id: 'fa-twitter', label: 'Twitter', style: 'fa-brands' },
];

const styleClassNames = new Set(
    styleOptions.flatMap(({ id }) => id.split(/\s+/)).filter(Boolean)
);

const legacyStyleAliases = {
    'fa-light': 'fal',
    'fa-regular': 'far',
    'fa-solid': 'fas',
    'fa-duotone': 'fad',
    'fa-brands': 'fab',
};

const legacyStyleClassNames = new Set(Object.values(legacyStyleAliases));

const nonIconClassNames = new Set([
    ...styleClassNames,
    'fa',
    ...legacyStyleClassNames,
    'fa-classic',
    'fa-fw',
    'fa-inverse',
    'fa-border',
    'fa-pull-left',
    'fa-pull-right',
    'fa-spin',
    'fa-pulse',
    'fa-xs',
    'fa-sm',
    'fa-lg',
    'fa-2x',
    'fa-3x',
    'fa-4x',
    'fa-5x',
]);

const findIconClass = (classNames) => classNames.find((className) => {
    return className.startsWith('fa-')
        && !nonIconClassNames.has(className)
        && !/^fa-(?:rotate|flip|stack|\d+x)(?:-|$)/.test(className);
});

const findStyle = (classNames) => {
    const matched = styleOptions.find(({ id }) => classNames.includes(id));

    if (matched) {
        return matched.id;
    }

    const legacyMatch = Object.entries(legacyStyleAliases).find(([, alias]) => {
        return classNames.includes(alias);
    });

    return legacyMatch?.[0] || 'fa-light';
};

const legacyAliasForStyle = (style) => legacyStyleAliases[style] || '';

const previewClasses = (option, selectedStyle = 'fa-light') => {
    const style = option.style || (selectedStyle === 'fa-brands' ? 'fa-light' : selectedStyle);

    return [style, legacyAliasForStyle(style), option.id].filter(Boolean).join(' ');
};

const registerIconPickerTrait = (editor) => {
    const traitManager = editor.TraitManager;

    if (!traitManager || traitManager.getTypes?.()[PICKER_TRAIT_TYPE]) {
        return;
    }

    traitManager.addType(PICKER_TRAIT_TYPE, {
        noLabel: true,
        createInput({ component }) {
            const root = document.createElement('div');
            const preview = document.createElement('div');
            const search = document.createElement('input');
            const grid = document.createElement('div');
            const buttons = [];

            root.setAttribute('data-fontawesome-icon-picker', '');
            root.style.cssText = 'display:grid;gap:10px;width:100%';
            preview.style.cssText = 'display:flex;min-height:52px;align-items:center;gap:12px;border:1px solid #d8dee6;border-radius:10px;padding:10px 12px;background:#fff;color:#142536';
            search.type = 'search';
            search.placeholder = 'Icon suchen …';
            search.setAttribute('aria-label', 'Font-Awesome-Icon suchen');
            search.style.cssText = 'box-sizing:border-box;width:100%;border:1px solid #cbd5e1;border-radius:8px;padding:8px 10px;background:#fff;color:#142536;font:inherit';
            grid.style.cssText = 'display:grid;grid-template-columns:repeat(auto-fill,minmax(64px,1fr));gap:7px;max-height:300px;overflow:auto;padding:2px';

            const previewIcon = document.createElement('i');
            const previewLabel = document.createElement('span');
            previewIcon.setAttribute('aria-hidden', 'true');
            previewIcon.style.cssText = 'display:inline-grid;width:34px;height:34px;place-items:center;border-radius:8px;background:#eef8f7;color:#087f86;font-size:20px';
            previewLabel.style.cssText = 'font-size:12px;font-weight:700';
            preview.append(previewIcon, previewLabel);

            const updateSelection = () => {
                const selectedIcon = component.get('faIcon') || 'fa-star';
                const selectedStyle = component.get('faStyle') || 'fa-light';
                const selectedOption = iconOptions.find(({ id }) => id === selectedIcon)
                    || { id: selectedIcon, label: selectedIcon };

                previewIcon.className = previewClasses(selectedOption, selectedStyle);
                previewLabel.textContent = selectedOption.label || selectedIcon;

                for (const { button, option } of buttons) {
                    const selected = option.id === selectedIcon;
                    button.setAttribute('aria-pressed', selected ? 'true' : 'false');
                    button.style.borderColor = selected ? '#0c968e' : '#d8dee6';
                    button.style.background = selected ? '#eaf7f6' : '#ffffff';
                    button.style.color = selected ? '#087f86' : '#334155';
                }
            };

            for (const option of iconOptions.filter(({ id }) => id !== '')) {
                const button = document.createElement('button');
                const icon = document.createElement('i');
                const label = document.createElement('span');

                button.type = 'button';
                button.title = option.label;
                button.setAttribute('aria-label', option.label);
                button.dataset.search = `${option.label} ${option.id}`.toLocaleLowerCase('de');
                button.style.cssText = 'display:grid;min-height:62px;place-items:center;gap:4px;border:1px solid #d8dee6;border-radius:9px;padding:7px 4px;background:#fff;color:#334155;cursor:pointer';
                icon.className = previewClasses(option);
                icon.setAttribute('aria-hidden', 'true');
                icon.style.fontSize = '19px';
                label.textContent = option.label;
                label.style.cssText = 'display:block;width:100%;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:9px;line-height:1.2;text-align:center';
                button.append(icon, label);
                button.addEventListener('click', () => {
                    component.set({
                        faStyle: option.style || (component.get('faStyle') === 'fa-brands' ? 'fa-light' : component.get('faStyle')),
                        faIconPreset: option.id,
                        faIcon: option.id,
                    });
                    updateSelection();
                });
                buttons.push({ button, option });
                grid.append(button);
            }

            search.addEventListener('input', () => {
                const query = search.value.trim().toLocaleLowerCase('de');

                for (const { button } of buttons) {
                    button.hidden = query !== '' && !button.dataset.search.includes(query);
                }
            });

            root.__updateFontAwesomePicker = updateSelection;
            root.append(preview, search, grid);
            updateSelection();

            return root;
        },
        onUpdate({ elInput }) {
            elInput?.__updateFontAwesomePicker?.();
        },
    });
};

export default function addFontAwesomeIconBlock(editor) {
    const { Components, Blocks } = editor;

    registerIconPickerTrait(editor);

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
                    faStyle: 'fa-light',
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
                            type: PICKER_TRAIT_TYPE,
                            name: 'faIconPreset',
                            label: false,
                            changeProp: true,
                        },
                        {
                            type: 'text',
                            name: 'faIcon',
                            label: 'Eigene Icon-Klasse',
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
                    const option = iconOptions.find(({ id }) => id === preset);

                    if (option) {
                        this.set({
                            faIcon: option.id,
                            ...(option.style ? { faStyle: option.style } : {}),
                        });
                    }
                },
                syncFontAwesomeClasses() {
                    const style = String(this.get('faStyle') || 'fa-light').trim();
                    const icon = String(this.get('faIcon') || 'fa-star').trim().split(/\s+/)[0];
                    const managedClassNames = new Set([
                        ...styleClassNames,
                        ...legacyStyleClassNames,
                        ...String(this._managedFaStyle || '').split(/\s+/),
                        this._managedFaIcon,
                    ]);
                    const preservedClassNames = this.getClasses().filter((className) => {
                        return className && !managedClassNames.has(className);
                    });

                    this.setClass([
                        ...new Set([
                            ...preservedClassNames,
                            style,
                            legacyAliasForStyle(style),
                            icon,
                        ].filter(Boolean)),
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
        label: 'Font Awesome Icons',
        category: {
            id: 'Basic',
            label: 'Basic',
            open: true,
        },
        media: '<span style="display:grid;place-items:center;gap:6px"><i class="fa-light fal fa-star" aria-hidden="true" style="font-size:30px"></i><small>Icon wählen</small></span>',
        content: {
            type: COMPONENT_TYPE,
            faStyle: 'fa-light',
            faIcon: 'fa-star',
            faIconPreset: 'fa-star',
        },
        select: true,
        attributes: {
            title: 'Font-Awesome-Icon mit Vorschau einfügen',
        },
    }, { at: 0 });
}

export {
    BLOCK_ID,
    COMPONENT_TYPE,
    PICKER_TRAIT_TYPE,
    iconOptions,
    styleOptions,
};
