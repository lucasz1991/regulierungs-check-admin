const SHARE_TRAIT_NAME = 'data-share';
const SHARE_VALUE = 'native';

export const shareLinkTrait = {
    type: 'select',
    name: SHARE_TRAIT_NAME,
    label: 'Link-Aktion',
    options: [
        { id: '', label: 'Normaler Link' },
        { id: SHARE_VALUE, label: 'News teilen (Bild, Titel & Kurztext)' },
    ],
};

const isLinkComponent = (component) => {
    const type = component?.get?.('type');
    const tagName = component?.get?.('tagName');

    return type === 'link' || String(tagName ?? '').toLowerCase() === 'a';
};

const syncShareAttributes = (component) => {
    const attributes = component.getAttributes?.() ?? {};

    if (attributes[SHARE_TRAIT_NAME] === SHARE_VALUE) {
        if (attributes['data-desktop-share'] !== 'clipboard') {
            component.addAttributes({ 'data-desktop-share': 'clipboard' });
        }

        return;
    }

    const attributesToRemove = [];

    if (attributes[SHARE_TRAIT_NAME] === '') {
        attributesToRemove.push(SHARE_TRAIT_NAME);
    }

    if (Object.prototype.hasOwnProperty.call(attributes, 'data-desktop-share')) {
        attributesToRemove.push('data-desktop-share');
    }

    if (attributesToRemove.length > 0) {
        component.removeAttributes(attributesToRemove);
    }
};

export const ensureShareLinkProperty = (component) => {
    if (!isLinkComponent(component)) {
        return false;
    }

    if (!component.getTrait?.(SHARE_TRAIT_NAME)) {
        component.addTrait(shareLinkTrait, {
            at: component.getTraits?.().length ?? 0,
        });
    }

    if (!component.__regulierungsCheckSharePropertyBound) {
        component.__regulierungsCheckSharePropertyBound = true;
        component.on?.('change:attributes', () => syncShareAttributes(component));
    }

    syncShareAttributes(component);

    return true;
};

export default function addSharePropertiesToLink(editor) {
    const enhanceLink = (component) => ensureShareLinkProperty(component);

    editor.on('component:create', enhanceLink);
    editor.on('component:selected', enhanceLink);

    // Bereits geladene Links erhalten die Eigenschaft ebenfalls. Es wird kein
    // neuer Block registriert und am sichtbaren Layout nichts veraendert.
    editor.Pages?.getAll?.().forEach((page) => {
        const wrapper = page.getMainComponent?.();

        wrapper?.findType?.('link').forEach(enhanceLink);
    });
}

export { SHARE_TRAIT_NAME, SHARE_VALUE };
