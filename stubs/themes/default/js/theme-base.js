/**
 * Default Synapse Theme — Base JavaScript (Shared Across All Layouts)
 *
 * Contains: Reusable Alpine.js components and utility functions loaded
 * on EVERY page: modal, tooltip, tabs, dropdown, toasts, collapsible,
 * formValidator, and utility helpers (debounce, formatDate, copyToClipboard).
 */

document.addEventListener('alpine:init', () => {
    // ============================================
    // MODAL COMPONENT
    // ============================================
    Alpine.data('modal', (initialOpen = false) => ({
        isOpen: initialOpen,

        open() {
            this.isOpen = true;
            document.body.style.overflow = 'hidden';
        },

        close() {
            this.isOpen = false;
            document.body.style.overflow = '';
        },

        closeOnEscape(event) {
            if (event.key === 'Escape') {
                this.close();
            }
        }
    }));

    // ============================================
    // TOOLTIP COMPONENT
    // ============================================
    Alpine.data('tooltip', (text = '') => ({
        text: text,
        show: false,

        mouseEnter() {
            this.show = true;
        },

        mouseLeave() {
            this.show = false;
        }
    }));

    // ============================================
    // TABS COMPONENT
    // ============================================
    Alpine.data('tabs', (defaultTab = 0) => ({
        activeTab: defaultTab,

        setTab(index) {
            this.activeTab = index;
        },

        isActive(index) {
            return this.activeTab === index;
        }
    }));

    // ============================================
    // DROPDOWN COMPONENT
    // ============================================
    Alpine.data('dropdown', () => ({
        isOpen: false,

        toggle() {
            this.isOpen = !this.isOpen;
        },

        close() {
            this.isOpen = false;
        },

        closeOnClickAway(event) {
            if (!this.$el.contains(event.target)) {
                this.close();
            }
        }
    }));

    // ============================================
    // TOAST NOTIFICATION SYSTEM
    // ============================================
    Alpine.data('toasts', () => ({
        items: [],
        nextId: 1,

        show(message, type = 'info', duration = 5000) {
            const id = this.nextId++;
            this.items.push({
                id,
                message,
                type,
                visible: true
            });

            // Auto-remove after duration
            setTimeout(() => {
                this.remove(id);
            }, duration);
        },

        success(message, duration = 5000) {
            this.show(message, 'success', duration);
        },

        error(message, duration = 5000) {
            this.show(message, 'error', duration);
        },

        warning(message, duration = 5000) {
            this.show(message, 'warning', duration);
        },

        info(message, duration = 5000) {
            this.show(message, 'info', duration);
        },

        remove(id) {
            const index = this.items.findIndex(item => item.id === id);
            if (index !== -1) {
                this.items[index].visible = false;
                setTimeout(() => {
                    this.items.splice(index, 1);
                }, 300);
            }
        }
    }));

    // ============================================
    // COLLAPSIBLE/ACCORDION COMPONENT
    // ============================================
    Alpine.data('collapsible', (initialOpen = false) => ({
        isOpen: initialOpen,

        toggle() {
            this.isOpen = !this.isOpen;
        },

        open() {
            this.isOpen = true;
        },

        close() {
            this.isOpen = false;
        }
    }));

    // ============================================
    // FORM VALIDATION HELPER
    // ============================================
    Alpine.data('formValidator', () => ({
        errors: {},
        touched: {},

        validate(field, rules) {
            this.touched[field] = true;
            // Add validation logic here
        },

        hasError(field) {
            return this.touched[field] && this.errors[field];
        },

        getError(field) {
            return this.errors[field];
        },

        clearErrors() {
            this.errors = {};
            this.touched = {};
        }
    }));
});

// ============================================
// UTILITY FUNCTIONS
// ============================================

/**
 * Debounce function for search and other input events
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * Format date for display
 */
function formatDate(date) {
    const now = new Date();
    const diff = now - new Date(date);
    const seconds = Math.floor(diff / 1000);
    const minutes = Math.floor(seconds / 60);
    const hours = Math.floor(minutes / 60);
    const days = Math.floor(hours / 24);

    if (days > 0) return `${days} day${days > 1 ? 's' : ''} ago`;
    if (hours > 0) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
    if (minutes > 0) return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
    return 'Just now';
}

/**
 * Copy text to clipboard
 */
async function copyToClipboard(text) {
    try {
        await navigator.clipboard.writeText(text);
        return true;
    } catch (err) {
        console.error('Failed to copy:', err);
        return false;
    }
}

// Export utility functions for global access
window.themeUtils = {
    debounce,
    formatDate,
    copyToClipboard
};
