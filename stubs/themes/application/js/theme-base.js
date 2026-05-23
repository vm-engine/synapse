/**
 * Application Synapse Theme — Base JavaScript (Shared Across All Layouts)
 *
 * Contains: Reusable Alpine.js components and utility functions loaded
 * on EVERY page: dropdown, toast, modal, tabs, collapsible, tooltip,
 * formValidator, and the m4vm utility namespace.
 */

document.addEventListener('alpine:init', () => {

    // ============================================
    // DROPDOWN COMPONENT
    // ============================================
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

    // ============================================
    // TOAST NOTIFICATION COMPONENT
    // ============================================
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

    // ============================================
    // MODAL COMPONENT
    // ============================================
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
    // ACCORDION/COLLAPSIBLE COMPONENT
    // ============================================
    Alpine.data('collapsible', (isOpen = false) => ({
        open: isOpen,

        toggle() {
            this.open = !this.open;
        }
    }));

    // ============================================
    // TOOLTIP COMPONENT
    // ============================================
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

    // ============================================
    // FORM VALIDATOR COMPONENT
    // ============================================
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

// ============================================
// UTILITY FUNCTIONS (Global Namespace)
// ============================================
window.m4vm = {
    /**
     * Copy text to clipboard
     */
    copyToClipboard(text) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(() => {
                console.log('Copied to clipboard');
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
            if (diff < 60000) return 'Just now';
            if (diff < 3600000) {
                const minutes = Math.floor(diff / 60000);
                return `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
            }
            if (diff < 86400000) {
                const hours = Math.floor(diff / 3600000);
                return `${hours} hour${hours > 1 ? 's' : ''} ago`;
            }
            if (diff < 604800000) {
                const days = Math.floor(diff / 86400000);
                return `${days} day${days > 1 ? 's' : ''} ago`;
            }
            return d.toLocaleDateString();
        }

        if (format === 'short') return d.toLocaleDateString();
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
        window.scrollTo({ top: top, behavior: 'smooth' });
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
