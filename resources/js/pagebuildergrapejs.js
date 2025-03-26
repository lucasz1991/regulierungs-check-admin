import grapesjs from 'grapesjs';
import 'grapesjs/dist/css/grapes.min.css';
import gjsBlocksBasic from 'grapesjs-blocks-basic'; 
import '@js/components/grapesjs-tailwind.min.js'; 
import 'grapesjs-plugin-tinymce6-editor';

window.initGrapesJs = async function() {
    if (!document.getElementById("studio-editor")) {
        return;
    }
    var selectedProject = document.getElementById('studio-editor').getAttribute('data-project');
    console.log("Initialisiere GrapesJS...");

    // Falls bereits ein Editor existiert, zerstören
    if (window.editor) {
        console.log("Bestehenden GrapesJS Editor zerstören...");
        window.editor.destroy();
        window.editor = null;
    }

    try {
        window.editor = grapesjs.init({
            container: '#studio-editor',
            fromElement: false,  // Falls ein bestehendes HTML geladen wird
            height: '100vh',
            width: '100%',
            assets: {
                storageType: 'self',

                onUpload: async ({ files }) => {
                    var body = new FormData();
                    for (var file of files) {
                        body.append('file', file);
                    }
                    var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    var response = await fetch('https://www.minifinds.de/api/pagebuilder/upload', {
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
                    var response = await fetch('https://www.minifinds.de/api/pagebuilder/assets', {
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
                    body.append('name', selectedProject);
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
            plugins: [
                gjsBlocksBasic, 
                'grapesjs-plugin-tinymce6-editor',
                'grapesjs-tailwind'
            ],
            pluginsOpts: {
                'gjs-blocks-basic': { flexGrid: true },
                'grapesjs-plugin-tinymce': {
                    options: {
                        toolbar: 'bold italic underline | alignleft aligncenter alignright | link',
                        menubar: false
                    }
                },
                'grapesjs-tailwind': { tailwindPlayCdn: './../css/components/tailwind.min.css' }
            },
            canvas: {
                styles: [
                    './../css/components/tailwind.min.css', // Tailwind im Editor aktivieren
                ],
            },
        });

        console.log("GrapesJS erfolgreich initialisiert!");
        return window.editor;
    } catch (error) {
        console.error("Fehler beim Initialisieren von GrapesJS:", error);
    }
}
