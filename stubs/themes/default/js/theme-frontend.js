/**
 * Default Synapse Theme — Frontend Layout JavaScript (Public Pages)
 *
 * Contains: frontend header behaviour (scroll shadow + mobile menu).
 * Loaded ONLY on public/frontend pages via resources/js/frontend.js.
 *
 * Methods/getters only — templates reference names, never inline
 * expressions, so this stays compatible with the CSP-safe Alpine build.
 */

const registerFrontendHeader = () => {
    Alpine.data('frontendHeader', () => ({
        scrolled: false,
        open: false,

        init() {
            this.onScroll();
            window.addEventListener('scroll', () => this.onScroll(), { passive: true });
        },

        onScroll() {
            this.scrolled = window.scrollY > 20;
        },

        toggle() {
            this.open = !this.open;
        },

        close() {
            this.open = false;
        },

        get headerClass() {
            return {
                'fe-header--scrolled': this.scrolled,
                'fe-header--open': this.open,
            };
        },

        get ariaExpanded() {
            return this.open ? 'true' : 'false';
        },
    }));
};

document.addEventListener('alpine:init', registerFrontendHeader);

if (window.Alpine) {
    registerFrontendHeader();
}
