/**
 * Default Synapse Theme — App Layout JavaScript (Backend/Admin)
 *
 * Contains: Sidebar toggle, dark/light mode toggle, notification system,
 * user menu dropdown, global search, and keyboard shortcuts.
 * Loaded ONLY on backend/admin pages via resources/js/app.js.
 */

document.addEventListener('alpine:init', () => {
    // ============================================
    // SIDEBAR COMPONENT
    // ============================================
    Alpine.data('themeSidebar', () => ({
        collapsed: Alpine.$persist(false).as('sidebarCollapsed'),
        activeSubmenu: null,

        init() {
            // Apply initial state to body classes
            this.updateBodyClass();
        },

        toggle() {
            this.collapsed = !this.collapsed;
            this.updateBodyClass();

            // Close all submenus when collapsing
            if (this.collapsed) {
                this.activeSubmenu = null;
            }
        },

        toggleSubmenu(menuId) {
            if (this.activeSubmenu === menuId) {
                this.activeSubmenu = null;
            } else {
                this.activeSubmenu = menuId;
            }
        },

        isSubmenuOpen(menuId) {
            return this.activeSubmenu === menuId;
        },

        updateBodyClass() {
            if (this.collapsed) {
                document.body.classList.add('sidebar-collapsed');
            } else {
                document.body.classList.remove('sidebar-collapsed');
            }
        }
    }));

    // ============================================
    // THEME TOGGLE COMPONENT (Dark/Light Mode)
    // ============================================
    Alpine.data('themeToggle', () => ({
        dark: Alpine.$persist(false).as('darkMode'),

        init() {
            // Apply initial theme
            this.applyTheme();
        },

        toggle() {
            this.dark = !this.dark;
            this.applyTheme();
        },

        applyTheme() {
            if (this.dark) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        }
    }));

    // ============================================
    // NOTIFICATION SYSTEM
    // ============================================
    Alpine.data('notifications', () => ({
        items: [],
        unreadCount: 0,
        isOpen: false,

        init() {
            // Load sample notifications (replace with real API call)
            this.items = [
                {
                    id: 1,
                    title: 'Welcome to Synapse!',
                    message: 'Your theming system is ready.',
                    time: '2 minutes ago',
                    read: false,
                    type: 'info'
                }
            ];
            this.updateUnreadCount();
        },

        toggle() {
            this.isOpen = !this.isOpen;
        },

        markAsRead(notificationId) {
            const notification = this.items.find(n => n.id === notificationId);
            if (notification) {
                notification.read = true;
                this.updateUnreadCount();
            }
        },

        markAllAsRead() {
            this.items.forEach(n => n.read = true);
            this.updateUnreadCount();
        },

        updateUnreadCount() {
            this.unreadCount = this.items.filter(n => !n.read).length;
        },

        remove(notificationId) {
            this.items = this.items.filter(n => n.id !== notificationId);
            this.updateUnreadCount();
        }
    }));

    // ============================================
    // USER MENU DROPDOWN
    // ============================================
    Alpine.data('userMenu', () => ({
        isOpen: false,

        toggle() {
            this.isOpen = !this.isOpen;
        },

        close() {
            this.isOpen = false;
        }
    }));

    // ============================================
    // SEARCH COMPONENT
    // ============================================
    Alpine.data('globalSearch', () => ({
        query: '',
        results: [],
        isSearching: false,
        isOpen: false,

        async search() {
            if (this.query.length < 2) {
                this.results = [];
                return;
            }

            this.isSearching = true;
            this.isOpen = true;

            // Simulate API call (replace with real search endpoint)
            setTimeout(() => {
                this.results = [
                    { type: 'page', title: 'Dashboard', url: '/dashboard' },
                    { type: 'page', title: 'Settings', url: '/settings' },
                    { type: 'doc', title: 'User Guide', url: '/docs/guide' },
                ];
                this.isSearching = false;
            }, 300);
        },

        clear() {
            this.query = '';
            this.results = [];
            this.isOpen = false;
        },

        selectResult(result) {
            window.location.href = result.url;
        }
    }));
});

// ============================================
// KEYBOARD SHORTCUTS (backend only)
// ============================================
document.addEventListener('keydown', (event) => {
    // Cmd/Ctrl + K for global search
    if ((event.metaKey || event.ctrlKey) && event.key === 'k') {
        event.preventDefault();
        const searchInput = document.querySelector('[x-data*="globalSearch"] input');
        if (searchInput) {
            searchInput.focus();
        }
    }

    // Cmd/Ctrl + B for sidebar toggle
    if ((event.metaKey || event.ctrlKey) && event.key === 'b') {
        event.preventDefault();
        Alpine.store('sidebar')?.toggle();
    }
});

// Smooth scroll to anchor links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            e.preventDefault();
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});
