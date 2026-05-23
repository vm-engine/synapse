/**
 * Application Synapse Theme — App Layout JavaScript (Backend/Admin)
 *
 * Contains: Global m4vm Alpine store (sidebar toggle, dark mode), keyboard
 * shortcuts (Cmd+K/B/D), sidebar menu component, and search component.
 * Loaded ONLY on backend/admin pages via resources/js/app.js.
 */

document.addEventListener('alpine:init', () => {

    // ============================================
    // GLOBAL THEME STORE
    // ============================================
    Alpine.store('m4vm', {
        sidebarOpen: Alpine.$persist(true).as('m4vm_sidebar_open'),
        sidebarCollapsed: Alpine.$persist(false).as('m4vm_sidebar_collapsed'),
        darkMode: Alpine.$persist(false).as('m4vm_dark_mode'),

        init() {
            // Apply dark mode class on init
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
            }
        },

        toggleSidebar() {
            this.sidebarOpen = !this.sidebarOpen;
        },

        toggleCollapse() {
            this.sidebarCollapsed = !this.sidebarCollapsed;
        },

        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            if (this.darkMode) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    });

    // ============================================
    // SIDEBAR MENU COMPONENT
    // ============================================
    Alpine.data('sidebarMenu', () => ({
        activeMenu: null,
        submenus: {},

        init() {
            // Get current route to set active menu
            const path = window.location.pathname;
            this.activeMenu = this.findActiveMenu(path);
        },

        findActiveMenu(path) {
            // Logic to determine active menu from path
            return path.split('/')[1] || 'dashboard';
        },

        toggleSubmenu(menuId) {
            if (!this.submenus[menuId]) {
                this.submenus[menuId] = true;
            } else {
                this.submenus[menuId] = !this.submenus[menuId];
            }
        },

        isSubmenuOpen(menuId) {
            return this.submenus[menuId] === true;
        },

        isActive(menuId) {
            return this.activeMenu === menuId;
        }
    }));

    // ============================================
    // SEARCH COMPONENT WITH DEBOUNCE
    // ============================================
    Alpine.data('search', () => ({
        query: '',
        results: [],
        loading: false,
        timeout: null,

        search() {
            clearTimeout(this.timeout);

            if (this.query.length < 2) {
                this.results = [];
                return;
            }

            this.timeout = setTimeout(() => {
                this.loading = true;
                this.performSearch();
            }, 300);
        },

        performSearch() {
            // Implement your search logic here
            // Example: fetch('/api/search?q=' + this.query)

            setTimeout(() => {
                this.results = [
                    { title: 'Sample Result 1', url: '#' },
                    { title: 'Sample Result 2', url: '#' }
                ];
                this.loading = false;
            }, 500);
        },

        clear() {
            this.query = '';
            this.results = [];
        }
    }));
});

// ============================================
// KEYBOARD SHORTCUTS (backend only)
// ============================================
document.addEventListener('keydown', (e) => {
    // Cmd/Ctrl + K for search
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.querySelector('input[type="text"][placeholder*="Search"]');
        if (searchInput) {
            searchInput.focus();
        }
    }

    // Cmd/Ctrl + B for sidebar toggle
    if ((e.metaKey || e.ctrlKey) && e.key === 'b') {
        e.preventDefault();
        Alpine.store('m4vm').toggleSidebar();
    }

    // Cmd/Ctrl + D for dark mode toggle
    if ((e.metaKey || e.ctrlKey) && e.key === 'd') {
        e.preventDefault();
        Alpine.store('m4vm').toggleDarkMode();
    }

    // Escape to close dropdowns and modals
    if (e.key === 'Escape') {
        // Trigger click.away for all open dropdowns
        window.dispatchEvent(new Event('click'));
    }
});

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    console.log('Application Theme (backend) loaded');

    // Initialize dark mode from store
    Alpine.store('m4vm')?.init();
});
