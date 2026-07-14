import '@grapesjs/studio-sdk/dist/style.css';
import '@grapesjs/studio-sdk/style';
import { createStudioEditor } from '@grapesjs/studio-sdk';
import { rteTinyMce } from '@grapesjs/studio-sdk-plugins';
import { iconifyComponent } from "@grapesjs/studio-sdk-plugins";
import { lightGalleryComponent } from "@grapesjs/studio-sdk-plugins";
import { fsLightboxComponent } from "@grapesjs/studio-sdk-plugins";
import { swiperComponent } from '@grapesjs/studio-sdk-plugins';
import { dialogComponent } from "@grapesjs/studio-sdk-plugins";
import addCustomBlocks from './components/grapesjs-blocks';
import addFontAwesomeIconBlock from './pagebuilder/fontawesome-icon';
import { addNewsDefaultLayoutBlock, appendNewsLayoutTemplate } from './pagebuilder/templates/news-layout-01';

let grapesJsInitializationPromise = null;
let grapesJsEditorElement = null;

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
            settingsMenu: {
                theme: false,
            },
            onEditor: (editor) => {
                window.editor = editor;
            },
            onReady: (editor) => {
                window.editor = editor;
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
                addNewsDefaultLayoutBlock(editor);
                addCustomBlocks(editor);
              } 
            ],
            layout: {
              default: {
                type: 'row',
                style: { height: '100%' },
                colors: {
                  global: {
                    focus: "rgba(37, 99, 235, 1)"
                  },
                  primary: {
                    background1: "rgba(101, 118, 95, 1)",
                    backgroundHover: "rgba(64, 84, 57, 1)"
                  }
                },
                children: [
                  {
                    type: 'sidebarLeft',
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
                    sidebarTop: { leftContainer: { buttons: [] } },
                  },
                  {
                    type: 'sidebarRight',
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
                    var htmldata = files.find(file => file.mimeType === 'text/html').content;
                    var body = new FormData();
                    body.append('id', selectedProject);
                    body.append('data', JSON.stringify(project));
                    body.append('html', htmldata);
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
