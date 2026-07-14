import './bootstrap';
import 'trix';
import collapse from '@alpinejs/collapse';
import mask from '@alpinejs/mask';
import resize from '@alpinejs/resize';
import intersect from '@alpinejs/intersect';
import sort from '@alpinejs/sort';

Alpine.plugin(collapse);
Alpine.plugin(mask);
Alpine.plugin(resize);
Alpine.plugin(intersect);
Alpine.plugin(sort);

let sidebarCollapseTimer = null;

function initMetisMenu() {
    if (!window.MetisMenu) {
        return;
    }

    const sideMenu = document.getElementById('side-menu');
    if (!sideMenu) {
        return;
    }

    if (window.__webreachMetisMenu && typeof window.__webreachMetisMenu.dispose === 'function') {
        window.__webreachMetisMenu.dispose();
    }

    window.__webreachMetisMenu = new window.MetisMenu('#side-menu');
}

function clearSidebarCollapseTimer() {
    if (sidebarCollapseTimer) {
        window.clearTimeout(sidebarCollapseTimer);
        sidebarCollapseTimer = null;
    }
}

function isDesktopHoverSidebar() {
    return window.innerWidth >= 1140 && Boolean(document.querySelector('.vertical-menu'));
}

function isSidebarHoveredOrFocused() {
    const activeElement = document.activeElement;
    const hoverInsideSidebar = document.querySelector('.vertical-menu:hover, .topbar-brand:hover');
    const focusInsideSidebar = activeElement?.closest('.vertical-menu, .topbar-brand');

    return Boolean(hoverInsideSidebar || focusInsideSidebar);
}

function setDesktopSidebarExpanded(expanded) {
    if (document.body.getAttribute('data-sidebar-collapsible') !== 'true') {
        return;
    }

    document.body.setAttribute('data-sidebar-expanded', expanded ? 'true' : 'false');
}

function scheduleDesktopSidebarCollapse() {
    clearSidebarCollapseTimer();

    sidebarCollapseTimer = window.setTimeout(() => {
        const activeElement = document.activeElement;
        const focusInsideSidebar = activeElement?.closest('.vertical-menu, .topbar-brand');
        const hoverInsideSidebar = document.querySelector('.vertical-menu:hover, .topbar-brand:hover');

        if (!focusInsideSidebar && !hoverInsideSidebar) {
            setDesktopSidebarExpanded(false);
        }
    }, 90);
}

function syncSidebarInteractionMode() {
    const hasSidebar = Boolean(document.querySelector('.vertical-menu'));
    if (!hasSidebar) {
        return;
    }

    const desktopMode = isDesktopHoverSidebar();
    document.body.setAttribute('data-sidebar-collapsible', desktopMode ? 'true' : 'false');

    if (desktopMode) {
        document.body.classList.remove('sidebar-enable');

        const isExpanded = document.body.getAttribute('data-sidebar-expanded') === 'true';
        const shouldStayExpanded = isExpanded || isSidebarHoveredOrFocused();

        document.body.setAttribute('data-sidebar-expanded', shouldStayExpanded ? 'true' : 'false');
        return;
    }

    clearSidebarCollapseTimer();
    document.body.setAttribute('data-sidebar-expanded', 'false');
}

function initLeftMenuCollapse() {
    document.querySelectorAll('.vertical-menu-btn').forEach((button) => {
        if (button.dataset.webreachBound === '1') {
            return;
        }

        button.dataset.webreachBound = '1';

        button.addEventListener('click', (event) => {
            event.preventDefault();

            if (isDesktopHoverSidebar()) {
                clearSidebarCollapseTimer();
                setDesktopSidebarExpanded(document.body.getAttribute('data-sidebar-expanded') !== 'true');
                return;
            }

            document.body.classList.toggle('sidebar-enable');
            initMenuItemScroll();
        });
    });
}

function initActiveMenu() {
    const pageUrl = window.location.href.split(/[?#]/)[0];
    const menuItems = Array.from(document.querySelectorAll('#sidebar-menu a'));
    const nestedLists = document.querySelectorAll('#sidebar-menu ul');

    menuItems.forEach((item) => item.classList.remove('active'));
    document.querySelectorAll('#sidebar-menu li.mm-active').forEach((item) => item.classList.remove('mm-active'));
    nestedLists.forEach((list) => {
        if (list.id !== 'side-menu') {
            list.classList.remove('mm-show');
        }
    });

    const exactMatches = menuItems.filter((item) => item.href === pageUrl);
    const fallbackMatches = menuItems.filter((item) => item.dataset.menuActive === 'true');
    const activeItems = exactMatches.length > 0 ? exactMatches : fallbackMatches;

    activeItems.forEach((item) => {
        item.classList.add('active');

        let currentLi = item.closest('li');
        while (currentLi) {
            currentLi.classList.add('mm-active');

            const parentUl = currentLi.parentElement;
            if (parentUl && parentUl.tagName === 'UL' && parentUl.id !== 'side-menu') {
                parentUl.classList.add('mm-show');
            }

            currentLi = parentUl ? parentUl.closest('li') : null;
        }
    });
}

function initMenuItemScroll() {
    setTimeout(() => {
        const sidebarMenu = document.getElementById('side-menu');
        const activeItem = sidebarMenu?.querySelector('.mm-active .active');

        if (!activeItem || activeItem.offsetTop <= 300) {
            return;
        }

        const verticalMenu = document.querySelector('.vertical-menu');
        const scroller = verticalMenu?.querySelector('.simplebar-content-wrapper');

        if (scroller) {
            scroller.scrollTop = activeItem.offsetTop;
        }
    }, 150);
}

function initFeather() {
    if (window.feather && typeof window.feather.replace === 'function') {
        window.feather.replace();
    }
}

function bindSidebarHoverElement(element) {
    if (element.dataset.webreachSidebarHoverBound === '1') {
        return;
    }

    element.dataset.webreachSidebarHoverBound = '1';

    element.addEventListener('mouseenter', () => {
        if (!isDesktopHoverSidebar()) {
            return;
        }

        clearSidebarCollapseTimer();
        setDesktopSidebarExpanded(true);
    });

    element.addEventListener('mouseleave', () => {
        if (!isDesktopHoverSidebar()) {
            return;
        }

        scheduleDesktopSidebarCollapse();
    });

    element.addEventListener('focusin', () => {
        if (!isDesktopHoverSidebar()) {
            return;
        }

        clearSidebarCollapseTimer();
        setDesktopSidebarExpanded(true);
    });

    element.addEventListener('focusout', () => {
        if (!isDesktopHoverSidebar()) {
            return;
        }

        scheduleDesktopSidebarCollapse();
    });
}

function initSidebarInteractions() {
    document.querySelectorAll('.vertical-menu, .topbar-brand').forEach(bindSidebarHoverElement);

    if (document.body.dataset.webreachSidebarInteractionsBound !== '1') {
        document.body.dataset.webreachSidebarInteractionsBound = '1';

        document.addEventListener(
            'pointerdown',
            (event) => {
                const target = event.target instanceof Element ? event.target : null;

                if (isDesktopHoverSidebar()) {
                    if (!target || !target.closest('.vertical-menu, .topbar-brand')) {
                        clearSidebarCollapseTimer();
                        setDesktopSidebarExpanded(false);
                    }

                    return;
                }

                if (!target || !target.closest('.vertical-menu, .vertical-menu-btn')) {
                    document.body.classList.remove('sidebar-enable');
                }
            },
            true
        );

        document.addEventListener('keydown', (event) => {
            if (event.key !== 'Escape') {
                return;
            }

            clearSidebarCollapseTimer();
            setDesktopSidebarExpanded(false);
            document.body.classList.remove('sidebar-enable');
        });

        window.addEventListener('resize', syncSidebarInteractionMode);
    }

    syncSidebarInteractionMode();
}

function initAdminLayout() {
    syncSidebarInteractionMode();
    initMetisMenu();
    initLeftMenuCollapse();
    initSidebarInteractions();
    initActiveMenu();
    initMenuItemScroll();
    initFeather();
}

document.addEventListener('DOMContentLoaded', initAdminLayout);
document.addEventListener('livewire:load', initAdminLayout);
document.addEventListener('livewire:navigated', initAdminLayout);

if (document.readyState !== 'loading') {
    initAdminLayout();
}

let pagebuilderModulePromise = null;
let pagebuilderInitializationPromise = null;
let pagebuilderInitializationElement = null;

function dispatchPagebuilderEvent(name, detail = {}) {
    window.dispatchEvent(new CustomEvent(`pagebuilder:${name}`, { detail }));
}

function loadPagebuilderModule({ retry = false } = {}) {
    if (retry) {
        pagebuilderModulePromise = null;
    }

    if (!pagebuilderModulePromise) {
        pagebuilderModulePromise = import('./pagebuilder.js').catch((error) => {
            pagebuilderModulePromise = null;
            throw error;
        });
    }

    return pagebuilderModulePromise;
}

async function initializePagebuilder({ force = false, retryModule = false } = {}) {
    const editorElement = document.getElementById('studio-editor');

    if (!editorElement) {
        return null;
    }

    if (pagebuilderInitializationPromise && pagebuilderInitializationElement === editorElement) {
        return pagebuilderInitializationPromise;
    }

    if (
        !force
        && editorElement.dataset.pagebuilderState === 'ready'
        && pagebuilderInitializationElement === editorElement
        && window.editor
    ) {
        return window.editor;
    }

    pagebuilderInitializationElement = editorElement;
    editorElement.dataset.pagebuilderState = 'loading';
    dispatchPagebuilderEvent('loading', { editorElement });

    const initialization = (async () => {
        await loadPagebuilderModule({ retry: retryModule });

        if (!editorElement.isConnected || document.getElementById('studio-editor') !== editorElement) {
            return null;
        }

        if (typeof window.initGrapesJs !== 'function') {
            throw new Error('Das PageBuilder-Modul stellt keine Initialisierungsfunktion bereit.');
        }

        const editor = await window.initGrapesJs({ force });

        if (!editor) {
            throw new Error('Der PageBuilder konnte nicht initialisiert werden.');
        }

        editorElement.dataset.pagebuilderState = 'ready';
        dispatchPagebuilderEvent('ready', { editor, editorElement });

        return editor;
    })();

    pagebuilderInitializationPromise = initialization;

    try {
        return await initialization;
    } catch (error) {
        if (editorElement.isConnected) {
            editorElement.dataset.pagebuilderState = 'error';
        }

        console.error('PageBuilder konnte nicht geladen werden.', error);
        dispatchPagebuilderEvent('error', {
            editorElement,
            error,
            message: error instanceof Error
                ? error.message
                : 'Der PageBuilder konnte nicht geladen werden.',
        });

        return null;
    } finally {
        if (pagebuilderInitializationPromise === initialization) {
            pagebuilderInitializationPromise = null;
        }
    }
}

function initializePagebuilderFromDom() {
    void initializePagebuilder();
}

window.initializePagebuilder = initializePagebuilder;
window.addEventListener('pagebuilder:retry', () => {
    void initializePagebuilder({ force: true, retryModule: true });
});

document.addEventListener('DOMContentLoaded', initializePagebuilderFromDom);
document.addEventListener('livewire:navigated', initializePagebuilderFromDom);

if (document.readyState !== 'loading') {
    initializePagebuilderFromDom();
}
