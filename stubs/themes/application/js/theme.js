/**
 * Member4VM Theme JavaScript
 * Alpine.js components and utilities
 */

document.addEventListener('alpine:init', () => {

    // Global Theme Store
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

    // Keyboard Shortcuts
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

    // Dropdown Component
    Alpine.data('dropdown', () => ({
        open: false,

        toggle() {
            this.open = !this.open;
        },

        close() {
            this.open = false;
        },

        init() {
            // Close on click outside
            this.$watch('open', value => {
                if (value) {
                    this.$nextTick(() => {
                        this.$refs.panel?.focus();
                    });
                }
            });
        }
    }));

    // Toast Notification Component
    Alpine.data('toast', () => ({
        show: false,
        message: '',
        type: 'info', // success, warning, danger, info
        duration: 3000,

        trigger(message, type = 'info', duration = 3000) {
            this.message = message;
            this.type = type;
            this.duration = duration;
            this.show = true;

            setTimeout(() => {
                this.hide();
            }, duration);
        },

        hide() {
            this.show = false;
        }
    }));

    // Modal Component
    Alpine.data('modal', (isOpen = false) => ({
        open: isOpen,

        show() {
            this.open = true;
            document.body.style.overflow = 'hidden';
        },

        hide() {
            this.open = false;
            document.body.style.overflow = '';
        },

        toggle() {
            if (this.open) {
                this.hide();
            } else {
                this.show();
            }
        }
    }));

    // Tabs Component
    Alpine.data('tabs', (defaultTab = 0) => ({
        activeTab: defaultTab,

        setTab(index) {
            this.activeTab = index;
        },

        isActive(index) {
            return this.activeTab === index;
        }
    }));

    // Accordion/Collapsible Component
    Alpine.data('collapsible', (isOpen = false) => ({
        open: isOpen,

        toggle() {
            this.open = !this.open;
        }
    }));

    // Tooltip Component
    Alpine.data('tooltip', (text = '') => ({
        tooltipText: text,
        show: false,
        timeout: null,

        showTooltip() {
            clearTimeout(this.timeout);
            this.timeout = setTimeout(() => {
                this.show = true;
            }, 500);
        },

        hideTooltip() {
            clearTimeout(this.timeout);
            this.show = false;
        }
    }));

    // Sidebar Menu Component
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

    // Search Component with Debounce
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

    // Form Validator Component
    Alpine.data('formValidator', () => ({
        errors: {},
        touched: {},

        validate(field, rules) {
            this.touched[field] = true;
            const value = this.$el.querySelector(`[name="${field}"]`)?.value;

            // Simple validation rules
            if (rules.required && !value) {
                this.errors[field] = 'This field is required';
                return false;
            }

            if (rules.email && value && !this.isValidEmail(value)) {
                this.errors[field] = 'Please enter a valid email';
                return false;
            }

            if (rules.minLength && value && value.length < rules.minLength) {
                this.errors[field] = `Minimum ${rules.minLength} characters required`;
                return false;
            }

            if (rules.maxLength && value && value.length > rules.maxLength) {
                this.errors[field] = `Maximum ${rules.maxLength} characters allowed`;
                return false;
            }

            delete this.errors[field];
            return true;
        },

        isValidEmail(email) {
            return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
        },

        hasError(field) {
            return this.touched[field] && this.errors[field];
        },

        getError(field) {
            return this.errors[field] || '';
        },

        clearError(field) {
            delete this.errors[field];
        }
    }));
});

// Utility Functions (Global Namespace)
window.m4vm = {
    /**
     * Copy text to clipboard
     */
    copyToClipboard(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                console.log('Copied to clipboard');
                // Optionally trigger a toast notification
            }).catch(err => {
                console.error('Failed to copy:', err);
            });
        } else {
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        }
    },

    /**
     * Debounce function
     */
    debounce(func, wait = 300) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Format date relative to now
     */
    formatDate(date, format = 'relative') {
        const d = new Date(date);
        const now = new Date();
        const diff = now - d;

        if (format === 'relative') {
            // Less than 1 minute
            if (diff < 60000) return 'Just now';

            // Less than 1 hour
            if (diff < 3600000) {
                const minutes = Math.floor(diff / 60000);
                return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
            }

            // Less than 1 day
            if (diff < 86400000) {
                const hours = Math.floor(diff / 3600000);
                return `${hours} hour${hours > 1 ? 's' : ''} ago`;
            }

            // Less than 1 week
            if (diff < 604800000) {
                const days = Math.floor(diff / 86400000);
                return `${days} day${days > 1 ? 's' : ''} ago`;
            }

            // Format as date
            return d.toLocaleDateString();
        }

        // Other formats
        if (format === 'short') {
            return d.toLocaleDateString();
        }

        if (format === 'long') {
            return d.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        if (format === 'time') {
            return d.toLocaleTimeString('en-US', {
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        return d.toLocaleString();
    },

    /**
     * Format number with thousand separators
     */
    formatNumber(number, decimals = 0) {
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(number);
    },

    /**
     * Format currency
     */
    formatCurrency(amount, currency = 'USD') {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: currency
        }).format(amount);
    },

    /**
     * Truncate text with ellipsis
     */
    truncate(text, length = 100, suffix = '...') {
        if (text.length <= length) return text;
        return text.substring(0, length).trim() + suffix;
    },

    /**
     * Slugify string
     */
    slugify(text) {
        return text
            .toString()
            .toLowerCase()
            .trim()
            .replace(/\s+/g, '-')
            .replace(/[^\w\-]+/g, '')
            .replace(/\-\-+/g, '-')
            .replace(/^-+/, '')
            .replace(/-+$/, '');
    },

    /**
     * Check if element is in viewport
     */
    isInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    },

    /**
     * Smooth scroll to element
     */
    scrollTo(elementOrSelector, offset = 0) {
        const element = typeof elementOrSelector === 'string'
            ? document.querySelector(elementOrSelector)
            : elementOrSelector;

        if (!element) return;

        const top = element.getBoundingClientRect().top + window.pageYOffset - offset;

        window.scrollTo({
            top: top,
            behavior: 'smooth'
        });
    },

    /**
     * Get random color
     */
    randomColor() {
        return '#' + Math.floor(Math.random() * 16777215).toString(16).padStart(6, '0');
    },

    /**
     * Download data as file
     */
    downloadFile(data, filename, type = 'text/plain') {
        const blob = new Blob([data], { type: type });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        window.URL.revokeObjectURL(url);
    }
};

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    console.log('Member4VM Theme loaded');

    // Initialize dark mode from store
    Alpine.store('m4vm')?.init();
});
