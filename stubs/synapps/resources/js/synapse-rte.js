// <x-synapse-rte> — Jodit bound to a Livewire property.
//
// Imported by the host's resources/js/app.js only while the rich-text editor is
// enabled (`rte.enabled` in synapps/config/apps.json, see `synapps:reconfig`).
// It is kept out of synapse-components.js because it imports Jodit, which a
// host without the editor does not have installed.
//
// Jodit owns its DOM (the Blade wrapper is wire:ignore'd) and is imported
// lazily, so pages without an editor never download it. Changes are pushed
// with a deferred $wire.set (sent with the next request, like wire:model).

const extensions = {};

// A module adds toolbar buttons by registering an extension, then naming it on
// the tag: <x-synapse-rte extension="name" :extension-config="[...]">.
// setup(rte, config) runs before the editor is built (rte.editor is set
// afterwards) and returns { buttons: [...], destroy() {} }.
window.synapseRte = {
    extend(name, setup) {
        extensions[name] = setup;
    },
};

const DEFAULT_BUTTONS = [
    'undo', 'redo', '|',
    'paragraph', '|',
    'bold', 'italic', 'underline', 'strikethrough', '|',
    'left', 'center', 'right', '|',
    'ul', 'ol', '|',
    'link',
];

// alpine:init fires once per full page load. After a wire:navigate from another layout this entry
// loads with Alpine already running, so register right away when it is (see the layouts' comment
// on keeping @themeScripts in <head>).
const registerSynapseRte = () => {
    Alpine.data('synapseRte', (config) => ({
        editor: null,
        extension: null,

        async init() {
            let Jodit;
            try {
                // 'jodit' is the ESM core; all.js adds the plugins the toolbar and
                // paste cleanup rely on (justify, source, paste, clean-html, ...).
                [{ Jodit }] = await Promise.all([
                    import('jodit'),
                    import('jodit/esm/plugins/all.js'),
                    import('jodit/es2021/jodit.min.css'),
                ]);
            } catch (error) {
                // Assets not built: keep the plain textarea editable and bound.
                console.error('[synapse] Jodit failed to load, using plain textarea.', error);
                this.$refs.textarea.addEventListener('input', (event) => {
                    this.$wire.set(config.field, event.target.value, false);
                });
                return;
            }

            const setup = config.extension ? extensions[config.extension] : null;
            this.extension = setup ? setup(this, config.extensionConfig || {}) : null;
            const extra = this.extension && this.extension.buttons ? this.extension.buttons : [];

            this.editor = Jodit.make(this.$refs.textarea, {
                height: config.height,
                toolbarAdaptive: false,
                askBeforePasteHTML: false,
                askBeforePasteFromWord: false,
                defaultActionOnPaste: 'insert_clear_html',
                buttons: [...(config.buttons || DEFAULT_BUTTONS), ...extra, '|', 'source'],
            });

            this.editor.events.on('change', (value) => this.$wire.set(config.field, value, false));
        },

        destroy() {
            if (this.extension && this.extension.destroy) {
                this.extension.destroy();
            }
            if (this.editor) {
                this.editor.destruct();
                this.editor = null;
            }
        },
    }));
};

if (window.Alpine) {
    registerSynapseRte();
} else {
    document.addEventListener('alpine:init', registerSynapseRte);
}
