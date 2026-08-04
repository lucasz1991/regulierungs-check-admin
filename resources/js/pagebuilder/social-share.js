const COMPONENT_TYPE = 'social-share';
const BLOCK_ID = 'social-share';

/*
 * Teilen-Block fuer den PageBuilder.
 *
 * Die Buttons teilen die Seite, auf der das gespeicherte HTML am Ende
 * ausgeliefert wird - die Ziel-URL steht deshalb nirgends im Markup, sondern
 * wird zur Laufzeit aus window.location gelesen.
 *
 * Zwei Eigenheiten der Speicherstrecke bestimmen die Umsetzung:
 *
 * 1. Das Verhalten haengt am GrapesJS-`script` der Komponente, nicht an einem
 *    <script> im Block-HTML - eingefuegte Script-Tags wuerden beim Parsen des
 *    Block-Inhalts verworfen.
 * 2. PagebuilderProject::sanitizeJs() entfernt jede vollstaendige
 *    http(s)-Adresse aus dem exportierten JavaScript. Die Share-Adressen
 *    werden deshalb aus 'https:' + '//host/pfad' zusammengesetzt; getrennt
 *    ueberlebt beides die Bereinigung.
 */

// Läuft pro Element im Frontend (und im Canvas-iframe). Wird von GrapesJS
// serialisiert - daher eigenstaendig, ES5 und ohne Modul-Bezuege.
function shareScript() {
    var group = this;

    function pageUrl() {
        return window.location.href.split('#')[0];
    }

    function pageTitle() {
        return document.title || '';
    }

    function targetFor(kind) {
        var proto = 'https:';
        var u = encodeURIComponent(pageUrl());
        var t = encodeURIComponent(pageTitle());

        if (kind === 'facebook') { return proto + '//www.facebook.com/sharer/sharer.php?u=' + u; }
        if (kind === 'x') { return proto + '//twitter.com/intent/tweet?url=' + u + '&text=' + t; }
        if (kind === 'linkedin') { return proto + '//www.linkedin.com/sharing/share-offsite/?url=' + u; }
        if (kind === 'whatsapp') { return proto + '//api.whatsapp.com/send?text=' + t + '%20' + u; }
        if (kind === 'email') { return 'mailto:?subject=' + t + '&body=' + encodeURIComponent(pageUrl()); }

        return null;
    }

    var links = group.querySelectorAll('[data-share]');

    for (var i = 0; i < links.length; i++) {
        (function (link) {
            if (link.getAttribute('data-share-wired')) { return; }
            link.setAttribute('data-share-wired', 'true');

            link.addEventListener('click', function (event) {
                var kind = link.getAttribute('data-share');

                if (kind === 'native') {
                    event.preventDefault();

                    if (navigator.share) {
                        navigator.share({ title: pageTitle(), url: pageUrl() })['catch'](function () {});
                    } else if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(pageUrl());
                    } else {
                        window.prompt('Link kopieren:', pageUrl());
                    }

                    return;
                }

                var target = targetFor(kind);

                if (!target) { return; }

                event.preventDefault();

                if (kind === 'email') {
                    window.location.href = target;
                } else {
                    window.open(target, '_blank', 'noopener,noreferrer,width=640,height=560');
                }
            });
        })(links[i]);
    }
}

const buttonStyle = 'display:inline-grid;width:44px;height:44px;place-items:center;border-radius:9999px;background:#084058;color:#ffffff;font-size:19px;text-decoration:none;transition:background .2s';

const shareButton = (kind, label, iconClasses) => (
    '<a href="#" data-share="' + kind + '" aria-label="' + label + '" title="' + label + '" style="' + buttonStyle + '">'
    + '<i class="' + iconClasses + '" aria-hidden="true"></i>'
    + '</a>'
);

const shareGroupHtml = [
    '<div data-share-group="true" style="box-sizing:border-box;display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:12px;padding:14px 0">',
    shareButton('facebook', 'Auf Facebook teilen', 'fa-brands fab fa-facebook-f'),
    shareButton('x', 'Auf X teilen', 'fa-brands fab fa-twitter'),
    shareButton('linkedin', 'Auf LinkedIn teilen', 'fa-brands fab fa-linkedin-in'),
    shareButton('whatsapp', 'Per WhatsApp teilen', 'fa-brands fab fa-whatsapp'),
    shareButton('email', 'Per E-Mail teilen', 'fa-light fal fa-envelope'),
    '</div>',
].join('');

export default function addSocialShareBlock(editor) {
    const { Components, Blocks } = editor;

    if (!Components.getType(COMPONENT_TYPE)) {
        Components.addType(COMPONENT_TYPE, {
            isComponent: (el) => {
                return !!(el && el.getAttribute && el.getAttribute('data-share-group') !== null);
            },
            model: {
                defaults: {
                    name: 'Teilen (Social Media)',
                    attributes: { 'data-share-group': 'true' },
                    script: shareScript,
                },
            },
        });
    }

    if (Blocks.get(BLOCK_ID)) {
        return Blocks.get(BLOCK_ID);
    }

    return Blocks.add(BLOCK_ID, {
        label: 'Teilen (Social Media)',
        category: {
            id: 'Basic',
            label: 'Basic',
            open: true,
        },
        media: '<span style="display:grid;place-items:center;gap:6px"><i class="fa-light fal fa-share-alt" aria-hidden="true" style="font-size:30px"></i><small>Teilen</small></span>',
        content: shareGroupHtml,
        select: true,
        attributes: {
            title: 'Teilen-Buttons für Social Media einfügen',
        },
    });
}

export { BLOCK_ID, COMPONENT_TYPE, shareGroupHtml };
