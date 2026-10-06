/**
 * Application Synapse Theme — Base JavaScript (Shared Across All Layouts)
 *
 * Contains: applicationThemeShell (dark mode for guest + frontend layouts) and
 * reusable Alpine.js components loaded on EVERY page: dropdown, modal, tabs,
 * collapsible, tooltip, formValidator. Toasts use <x-synapse-toastr />.
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
// THEME SHELL (guest + frontend layouts)
// Persisted dark mode, same 'darkMode' key as applicationAppShell and the
// default theme. Sets .dark on <html> so dark: variants also match <body>.
// ============================================
const registerThemeShell = () => {
    Alpine.data('applicationThemeShell', () => ({
        darkMode: Alpine.$persist(false).as('darkMode'),

        init() {
            this.applyDarkMode();
        },

        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            this.applyDarkMode();
        },

        applyDarkMode() {
            document.documentElement.classList.toggle('dark', this.darkMode);
        },
    }));
};

document.addEventListener('alpine:init', registerThemeShell);

if (window.Alpine) {
    registerThemeShell();
}
