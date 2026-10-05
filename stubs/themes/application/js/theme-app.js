/**
 * Application Synapse Theme — App Layout JavaScript (Backend/Admin)
 *
 * Contains: applicationAppShell (dark mode + sidebar state, persisted),
 * applicationSidebarSubmenu, and the Cmd/Ctrl+B sidebar shortcut.
 * Global search (Cmd/Ctrl+K) is handled by <livewire:synsearch::palette />.
 * Loaded ONLY on backend/admin pages via resources/js/app.js.
 */

const registerAppShell = () => {
    Alpine.data('applicationAppShell', () => ({
        // Same key as the default theme so the choice survives theme switches
        // and carries between admin, auth and public pages.
        darkMode: Alpine.$persist(false).as('darkMode'),
        sidebarOpen: Alpine.$persist(true).as('sidebarOpen'),
        sidebarCollapsed: Alpine.$persist(false).as('sidebarCollapsed'),

        init() {
            this.applyDarkMode();
        },

        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            this.applyDarkMode();
        },

        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },

        closeSidebar() {
            this.sidebarOpen = false;
        },

        // Getter so the template stays a bare name (the CSP-safe evaluator can't reach `window`).
        get showMobileOverlay() {
            return this.sidebarOpen && window.innerWidth < 1024;
        },

        applyDarkMode() {
            document.documentElement.classList.toggle('dark', this.darkMode);
        },
    }));

    // Sidebar menu item with a submenu (layouts/components/sidebar-menu.blade.php).
    Alpine.data('applicationSidebarSubmenu', (initialOpen) => ({
        open: initialOpen,

        toggle(key) {
            this.open = !this.open;
            this.selected = this.selected === key ? '' : key;
        },
    }));
};

document.addEventListener('alpine:init', registerAppShell);

if (window.Alpine) {
    registerAppShell();
}

document.addEventListener('keydown', (event) => {
    // Only on pages rendered by this theme's app layout (core fallback layouts have their own shell).
    const shell = window.Alpine?.$data(document.body);

    if ((event.metaKey || event.ctrlKey) && event.key === 'b' && typeof shell?.toggleSidebar === 'function') {
        event.preventDefault();
        shell.toggleSidebar();
    }
});
