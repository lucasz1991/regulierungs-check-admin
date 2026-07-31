import '@grapesjs/studio-sdk/dist/style.css';
import '@grapesjs/studio-sdk/style';
// Nach den SDK-Styles importiert, damit die eigenen Regeln gewinnen.
import '../css/pagebuilder-ui.css';
import { createStudioEditor } from '@grapesjs/studio-sdk';
import { rteTinyMce } from '@grapesjs/studio-sdk-plugins';
import { iconifyComponent } from "@grapesjs/studio-sdk-plugins";
import { lightGalleryComponent } from "@grapesjs/studio-sdk-plugins";
import { fsLightboxComponent } from "@grapesjs/studio-sdk-plugins";
import { swiperComponent } from '@grapesjs/studio-sdk-plugins';
import { dialogComponent } from "@grapesjs/studio-sdk-plugins";
import addCustomBlocks from './components/grapesjs-blocks';
import addFontAwesomeIconBlock from './pagebuilder/fontawesome-icon';
import { appendNewsLayoutTemplate } from './pagebuilder/templates/news-layout-01';

let grapesJsInitializationPromise = null;
let grapesJsEditorElement = null;

const SIDEBAR_LEFT_TOGGLE = 'studio:sidebarLeft:toggle';
const SIDEBAR_LEFT_GET = 'studio:sidebarLeft:get';
const SIDEBAR_LEFT_SET = 'studio:sidebarLeft:set';
const SIDEBAR_RIGHT_SET = 'studio:sidebarRight:set';

const SIDEBAR_LEFT_CLASS = 'rc-sidebar-left';
const SIDEBAR_RIGHT_CLASS = 'rc-sidebar-right';
const SIDEBAR_LEFT_TOGGLE_CLASS = 'rc-sidebar-left-toggle';

// Eigenes Event, damit der Umschalt-Button auch dann nachzieht, wenn die
// Sidebar nicht ueber ihn geschlossen wurde (Klick daneben, Escape).
const SIDEBAR_LEFT_EVENT = 'rc:sidebar-left';

// Zustand des Umschalters fuer die linke Sidebar (Blocks, Elements, Vorlagen).
const sidebarLeftToggleState = (isOpen) => ({
    icon: isOpen ? 'chevronLeft' : 'chevronRight',
    tooltip: isOpen
        ? 'Blöcke, Elemente und Vorlagen einklappen'
        : 'Blöcke, Elemente und Vorlagen ausklappen',
    active: isOpen,
});

const isSidebarLeftOpen = (editor) => {
    return editor.runCommand(SIDEBAR_LEFT_GET)?.visible !== false;
};

const setSidebarLeftOpen = (editor, visible) => {
    editor.runCommand(SIDEBAR_LEFT_SET, { visible });
    editor.trigger(SIDEBAR_LEFT_EVENT, { visible });
};

// Aufraeumen der Dokument-Listener, wenn der Editor neu aufgebaut wird.
let releaseEditorChrome = null;

/**
 * Beide Sidebars starten eingeklappt. Die linke oeffnet nur auf Wunsch und
 * schliesst wieder, sobald ausserhalb geklickt wird. Die rechte folgt der
 * Auswahl im Canvas, weil ihre Panels ohne markiertes Element leer sind.
 */
const setupEditorChrome = (editor) => {
    releaseEditorChrome?.();

    setSidebarLeftOpen(editor, false);
    editor.runCommand(SIDEBAR_RIGHT_SET, { visible: false });

    const syncRightSidebar = () => {
        editor.runCommand(SIDEBAR_RIGHT_SET, {
            visible: editor.getSelectedAll().length > 0,
        });
    };

    const closeLeftSidebarOnOutsideClick = (event) => {
        if (!isSidebarLeftOpen(editor)) {
            return;
        }

        // Klicks im Canvas stammen aus dem iframe und treffen hier nie zu -
        // genau das ist gewollt, sie sollen die Sidebar schliessen.
        const insideSidebar = event.target?.closest?.(
            `.${SIDEBAR_LEFT_CLASS}, .${SIDEBAR_LEFT_TOGGLE_CLASS}`
        );

        if (insideSidebar) {
            return;
        }

        setSidebarLeftOpen(editor, false);
    };

    const closeLeftSidebarOnEscape = (event) => {
        if (event.key === 'Escape' && isSidebarLeftOpen(editor)) {
            setSidebarLeftOpen(editor, false);
        }
    };

    // Der Canvas ist ein eigenes Dokument, dessen Events das Hauptdokument
    // nicht erreichen. Nach jedem Frame-Wechsel neu verdrahten.
    const canvasDocuments = new Set();
    const observeCanvasDocument = () => {
        const canvasDocument = editor.Canvas?.getDocument?.();

        if (!canvasDocument || canvasDocuments.has(canvasDocument)) {
            return;
        }

        canvasDocuments.add(canvasDocument);
        canvasDocument.addEventListener('pointerdown', closeLeftSidebarOnOutsideClick, true);
    };

    document.addEventListener('pointerdown', closeLeftSidebarOnOutsideClick, true);
    document.addEventListener('keydown', closeLeftSidebarOnEscape);
    editor.on('component:toggled', syncRightSidebar);
    editor.on('canvas:frame:load', observeCanvasDocument);
    observeCanvasDocument();

    releaseEditorChrome = () => {
        document.removeEventListener('pointerdown', closeLeftSidebarOnOutsideClick, true);
        document.removeEventListener('keydown', closeLeftSidebarOnEscape);
        canvasDocuments.forEach((canvasDocument) => {
            canvasDocument.removeEventListener('pointerdown', closeLeftSidebarOnOutsideClick, true);
        });
        canvasDocuments.clear();
        releaseEditorChrome = null;
    };
};

// Farben der Admin-Marke aus tailwind.config.js. Das SDK liest sie
// ausschliesslich aus `customTheme`, nicht aus der Layout-Konfiguration.
const editorTheme = {
    default: {
        colors: {
            global: {
                background1: '#ffffff',
                background2: '#f6f8fa',
                background3: '#eef2f6',
                backgroundHover: '#e6edf3',
                text: '#1f2d3a',
                border: '#dfe6ec',
                focus: '#0b5879',
                placeholder: '#8b9aa8',
            },
            primary: {
                background1: '#084058',
                background2: '#0b5879',
                background3: '#0d648a',
                backgroundHover: '#0b5879',
                text: '#ffffff',
            },
            component: {
                background1: '#0c968e',
                backgroundHover: '#10b3aa',
                text: '#ffffff',
            },
        },
    },
};

window.initGrapesJs = async function({ force = false } = {}) {
    const editorElement = document.getElementById('studio-editor');

    if (!editorElement) {
        return null;
    }

    if (!force && grapesJsInitializationPromise && grapesJsEditorElement === editorElement) {
        return grapesJsInitializationPromise;
    }

    if (!force && window.editor && grapesJsEditorElement === editorElement) {
        return window.editor;
    }

    grapesJsEditorElement = editorElement;
    const initialization = initializeGrapesJsEditor(editorElement);
    grapesJsInitializationPromise = initialization;

    try {
        return await initialization;
    } catch (error) {
        if (grapesJsEditorElement === editorElement) {
            window.editor = null;
            grapesJsEditorElement = null;
        }

        throw error;
    } finally {
        if (grapesJsInitializationPromise === initialization) {
            grapesJsInitializationPromise = null;
        }
    }
};

async function initializeGrapesJsEditor(editorElement) {

    const selectedProject = editorElement.getAttribute('data-project');

    if (!selectedProject) {
        throw new Error('Dem PageBuilder fehlt eine Projekt-ID.');
    }

    const licenseKey = editorElement.getAttribute('data-license');
    const apiUrl = editorElement.getAttribute('data-api-url');

    console.info('Initialisiere GrapesJS Studio.', { projectId: selectedProject, apiUrl });
    if (window.editor) {
        console.log("Bestehenden GrapesJS Editor zerstören...");
        releaseEditorChrome?.();
        window.editor.destroy();
        window.editor = null;
    }
    try {
        let resolveEditorReady;
        const editorReady = new Promise((resolve) => {
            resolveEditorReady = resolve;
        });

        await createStudioEditor({
            root: '#studio-editor',
            licenseKey: licenseKey,
            theme: 'light',
            customTheme: editorTheme,
            settingsMenu: {
                theme: false,
            },
            onEditor: (editor) => {
                window.editor = editor;
            },
            onReady: (editor) => {
                window.editor = editor;
                setupEditorChrome(editor);
                resolveEditorReady(editor);
            },
            plugins: [
              rteTinyMce.init({
                licenseKey,
                enableOnClick: true,
                loadConfig: ({ component, config }) => {
                  const demoRte = component.get('demorte');
                  if (demoRte === 'fixed') {
                    return {
                      toolbar:
                        'bold italic underline strikethrough | alignleft aligncenter alignright alignjustify | link image media',
                      fixed_toolbar_container_target: document.querySelector('.rteContainer')
                    };
                  } else if (demoRte === 'quickbar') {
                    return {
                      plugins: `${config.plugins} quickbars`,
                      toolbar: false,
                      quickbars_selection_toolbar: 'bold italic underline strikethrough | quicklink image'
                    };
                  }
                  return {};
                }
              }),
              iconifyComponent?.init({
                licenseKey,
                block: { category: 'Extra', label: 'Iconify' }
              }),
              fsLightboxComponent?.init({
                licenseKey,
                block: { category: 'Extra', label: 'FS Lightbox' }
              }),
              lightGalleryComponent?.init({
                licenseKey,
                block: { category: 'Extra', label: 'Light Gallery' }
              }),
              swiperComponent?.init({
                licenseKey,
                block: false
              }),
              dialogComponent.init({
                licenseKey,
                block: { category: 'Extra', label: 'My Dialog' }
              }),
              editor => {
                addFontAwesomeIconBlock(editor);
                addCustomBlocks(editor);
              }
            ],
            layout: {
              default: {
                type: 'row',
                style: { height: '100%' },
                // Farben stehen in `customTheme`. Das SDK wertet sie
                // ausschliesslich dort aus, nicht in der Layout-Zeile.
                children: [
                  {
                    type: 'sidebarLeft',
                    className: SIDEBAR_LEFT_CLASS,
                    children: {
                      type: 'tabs',
                      value: 'blocks',
                      tabs: [
                        {
                          id: 'blocks',
                          label: 'Blocks',
                          children: { type: 'panelBlocks', style: { height: '100%' } },
                        },
                        {
                          id: 'layers',
                          label: 'Elements',
                          children: { type: 'panelLayers', style: { height: '100%' } },
                        },
                        {
                          id: 'templates',
                          label: 'Vorlagen',
                          children: {
                            type: 'panelTemplates',
                            content: { itemsPerRow: 1 },
                            style: { height: '100%' }
                          },
                        },
                      ],
                    },
                  },
                  {
                    type: 'canvasSidebarTop',
                    sidebarTop: {
                      leftContainer: {
                        buttons: [
                          {
                            id: 'sidebar-left-toggle',
                            type: 'button',
                            size: 's',
                            variant: 'outline',
                            className: SIDEBAR_LEFT_TOGGLE_CLASS,
                            ...sidebarLeftToggleState(false),
                            editorEvents: {
                              [SIDEBAR_LEFT_EVENT]: ({ fromEvent, setState }) => {
                                setState(sidebarLeftToggleState(!!fromEvent?.visible));
                              },
                            },
                            onClick: ({ editor, setState }) => {
                              editor.runCommand(SIDEBAR_LEFT_TOGGLE);
                              const isOpen = isSidebarLeftOpen(editor);
                              editor.trigger(SIDEBAR_LEFT_EVENT, { visible: isOpen });
                              setState(sidebarLeftToggleState(isOpen));
                            },
                          },
                        ],
                      },
                    },
                  },
                  {
                    type: 'sidebarRight',
                    className: SIDEBAR_RIGHT_CLASS,
                    children: {
                      type: 'tabs',
                      value: 'styles',
                      tabs: [
                        {
                          id: 'styles',
                          label: 'Styles',
                          children: {
                            type: 'column',
                            style: { height: '100%' },
                            children: [
                              { type: 'panelSelectors', style: { padding: 5 } },
                              { type: 'panelStyles' },
                            ],
                          },
                        },
                        {
                          id: 'props',
                          label: 'Properties',
                          children: { type: 'panelProperties', style: { padding: 5, height: '100%' } },
                        },
                        {
                          id: 'amimations',
                          label: 'Effects',
                          children: [
                            { type: 'panelSelectors', style: { padding: 5 } },
                            { type: 'panelAnimations', style: { padding: 25, height: '100%' } }
                          ],
                        },
                      ],
                    },
                  },
                ],
              },
          },
            project: { 
                type: 'web'
            },
            pages: {
                add: false,
                duplicate: false,
                remove: false,
                select: false,
                settings: false
            },
            canvas: {
                styles: [
                    '/build/css/tailwind.min.css',
                    '/adminresources/fontawesome6/css/all.min.css',
                ],
            },
            templates: {
                onLoad: async ({ fetchCommunityTemplates }) => {
                    let communityTemplates = [];

                    try {
                        communityTemplates = await fetchCommunityTemplates();
                    } catch (error) {
                        console.warn('Community-Vorlagen konnten nicht geladen werden.', error);
                    }

                    return appendNewsLayoutTemplate(communityTemplates);
                },
            },
            assets: {
                storageType: 'self',
                onUpload: async ({ files }) => {
                    var body = new FormData();
                    for (var file of files) {
                        body.append('file', file);
                    }
                    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    var response = await fetch( apiUrl + '/api/pagebuilder/upload', {
                        method: 'POST',
                        body,
                        headers: { 'Authorization': 'Bearer ' + localStorage.getItem('auth_token'), 'X-CSRF-TOKEN': csrfToken }
                    });
                    console.log(response);
                    if (!response.ok) {
                        console.error('Bild-Upload fehlgeschlagen');
                        return [];
                    }
                    var result = await response.json();
                    return [{ src: result.url }];
                },
                onLoad: async () => {
                    var response = await fetch( apiUrl + '/api/pagebuilder/assets', {
                        headers: { 'Authorization': 'Bearer ' + localStorage.getItem('auth_token') },
                    });
                    console.log(response);
                    if (!response.ok) {
                        console.error('Fehler beim Laden der Assets');
                        return [];
                    }
                    return await response.json();
                }
            },
            storage: {
                type: 'self',
                onSave: async ({ project, editor }) => {
                    var files = await editor.runCommand('studio:projectFiles');
                    var htmlFile = files.find(file => file.mimeType === 'text/html');
                    var cssFile = files.find(file => file.mimeType === 'text/css');

                    if (!htmlFile) {
                        throw new Error('Der PageBuilder hat keine HTML-Datei erzeugt.');
                    }

                    var htmldata = htmlFile.content;
                    var cssdata = cssFile?.content ?? editor.getCss({ keepUnusedStyles: true }) ?? '';
                    var body = new FormData();
                    body.append('id', selectedProject);
                    body.append('data', JSON.stringify(project));
                    body.append('html', htmldata);
                    body.append('css', cssdata);
                    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    var response = await fetch('/admin/pagebuilder/save', {
                        method: 'POST',
                        body,
                        headers: {
                            'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),
                            'X-CSRF-TOKEN': csrfToken
                        },
                    });
                    console.log(response);
                    if (!response.ok) {
                        console.error('Speichern fehlgeschlagen');
                    } else {
                        console.log('Projekt gespeichert!');
                    }
                },
                onLoad: async () => {
                    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    var response = await fetch('/admin/pagebuilder/load/'+selectedProject, {
                        headers: { 'Authorization': 'Bearer ' + localStorage.getItem('auth_token'),'X-CSRF-TOKEN': csrfToken },
                    });
                    console.log(response);
                    if (!response.ok) {
                        console.error('Laden fehlgeschlagen');
                        return {};
                    }
                    var projektJson = await response.json();
                    return  { project: projektJson };
                },
                autosaveChanges: 100,
                autosaveIntervalMs: 10000
            },
            identity: {
                id: "1MZssHHwuOi2kNaH"
            }
        });
        const editor = await editorReady;
        console.log("GrapesJS Studio erfolgreich initialisiert!");
        return editor;
    } catch (error) {
        console.error("Fehler beim Initialisieren von GrapesJS Studio:", error);
        throw error;
    }
}
