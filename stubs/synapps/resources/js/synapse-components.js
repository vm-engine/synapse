/**
 * Alpine.data() registrations for vm-engine/synapse's Blade/Livewire components.
 *
 * These used to live in an inline <script> inside each component view. Livewire
 * doesn't execute <script> tags that arrive through a morph, so a component whose
 * first appearance on a page came from a Livewire update (e.g. the status toggler
 * after leaving a users list's trash view) was left with an undefined Alpine.data
 * name. Registering everything once here, before Alpine starts, avoids that.
 *
 * Blade values the old inline scripts interpolated are now passed through each
 * component's x-data arguments (as json_encode()d literals - the CSP-safe Alpine
 * build has no JSON global, so never @js() inside x-data).
 *
 * Published by synapps:patch from vm-engine/synapse's stub; keep the two in sync.
 */
import flatpickr from "flatpickr";
import monthSelectPlugin from "flatpickr/dist/plugins/monthSelect";
import "flatpickr/dist/plugins/monthSelect/style.css";

// Livewire CSP build: assigning through $wire (e.g. the x-modelable="$wire.value"
// binding Livewire adds to every #[Modelable] child) throws "properties[name] is
// not a function". Alpine's CSP evaluator reads `obj.constructor` before any
// member assignment, and Livewire's $wire proxy answers `property in aliases` -
// true for inherited Object.prototype keys - then calls a non-existent property
// getter (livewire/livewire js/$wire.js, still the case in v4.4.7). Answer those
// inherited keys before they reach Livewire's proxy.
Livewire.hook("component.init", ({ component }) => {
    const wire = component.$wire;

    component.$wire = component.el.$wire = new Proxy(wire, {
        get(target, property) {
            if (typeof property === "string" && property in Object.prototype) {
                return Object.prototype[property];
            }

            return wire[property];
        },
        set(target, property, value) {
            wire[property] = value;

            return true;
        },
    });
});

// <x-synapse-adv-select>
Alpine.data('synapseAdvSelect', (preload, multiple, searchMethod, loadMethod, wireModel, labelField,
    valueField,
    staticOptions, minSearchLength, isLive, allowCreate, autoLoad, disabled) => ({
    search: '',
    results: [],
    selected: preload,
    multiple,
    open: false,
    activeIndex: -1,
    searchMethod,
    loadMethod,
    wireModel,
    labelField,
    valueField,
    staticOptions,
    minSearchLength,
    isLive,
    allowCreate,
    autoLoad,
    disabled,

    async init() {
        if (this.autoLoad && this.selected.length === 0) {
            await this.loadInitialValues();
        }

        if (this.selected && this.selected.length > 0) {
            this.syncWithWire();
        }

        Livewire.interceptMessage(({
            onSuccess
        }) => {
            onSuccess(() => {
                if (document.activeElement === this.$refs.search &&
                    this.search
                    .length > 0) {
                    this.open = true;
                }
            });
        });

        window.addEventListener('reset-tag-input', () => {
            this.clearSelection();
        });

        if (this.$el.closest('.syn-modal-panel, .syn-drawer-panel')) {
            this.floatDropdownInPanel();
        }
    },

    /**
     * A modal or drawer panel is a scroll container, so an absolutely positioned
     * dropdown would stretch it (and be clipped by it). Pin the open dropdown to
     * the viewport instead, under or over the box, whichever side has more room.
     * Both panels are untransformed while open, so `fixed` is viewport-relative.
     */
    floatDropdownInPanel() {
        const dropdown = this.$el.querySelector('.adv-select-dropdown');
        const gap = 4;
        const edge = 8;
        const maxHeight = 240; // the dropdown's own max-h-60

        const place = () => {
            const box = this.$refs.box.getBoundingClientRect();
            const below = window.innerHeight - box.bottom - gap - edge;
            const above = box.top - gap - edge;
            const openUp = below < maxHeight && above > below;

            Object.assign(dropdown.style, {
                position: 'fixed',
                left: `${box.left}px`,
                width: `${box.width}px`,
                margin: '0',
                maxHeight: `${Math.min(maxHeight, openUp ? above : below)}px`,
                top: openUp ? 'auto' : `${box.bottom + gap}px`,
                bottom: openUp ? `${window.innerHeight - box.top + gap}px` : 'auto',
            });
        };

        this.$watch('open', (isOpen) => {
            if (isOpen) {
                place();
                window.addEventListener('scroll', place, true);
                window.addEventListener('resize', place);
            } else {
                window.removeEventListener('scroll', place, true);
                window.removeEventListener('resize', place);
            }
        });
    },

    async loadInitialValues() {
        if (!this.$wire) return;

        try {
            const currentValue = this.$wire.get(this.wireModel);

            if (currentValue === null || currentValue === undefined || (Array
                    .isArray(
                        currentValue) && currentValue.length === 0)) {
                return;
            }

            const values = Array.isArray(currentValue) ? currentValue : [
                currentValue
            ];

            const foundInStatic = [];
            for (const val of values) {
                const found = this.staticOptions.find(o => o[this.valueField] ==
                    val);
                if (found) {
                    foundInStatic.push(found);
                }
            }

            if (foundInStatic.length === values.length) {
                this.selected = foundInStatic;
                return;
            }

            if (this.loadMethod && this.$wire?.call) {
                const loaded = await this.$wire.call(this.loadMethod, this
                    .multiple ? values :
                    values[0]);

                if (loaded) {
                    this.selected = Array.isArray(loaded) ? loaded : [loaded];
                    return;
                }
            }

            this.selected = values.map(val => ({
                [this.valueField]: val,
                [this.labelField]: val
            }));
        } catch (e) {
            console.error('Error loading initial values:', e);
        }
    },

    focusInput() {
        if (this.disabled) return;
        this.open = true;
        this.$nextTick(() => this.$refs.search?.focus());
    },

    toggleOpen() {
        if (this.disabled) return;
        if (this.open) {
            this.open = false;
            this.$refs.search?.blur();
        } else {
            this.focusInput();
        }
    },

    isActive(index) {
        return index === this.activeIndex;
    },

    /** Indents a tree option by its `depth` (0 = top level); the label itself stays bare. */
    optionIndent(item) {
        const depth = Number(item.depth) || 0;
        return depth > 0 ? { paddingInlineStart: `${0.5 + depth}rem` } : {};
    },

    async fetch() {
        const query = this.search.toLowerCase();

        let staticResults = this.staticOptions.filter(o =>
            o[this.labelField].toLowerCase().includes(query)
        );

        if (this.searchMethod && query.length > this.minSearchLength && this.$wire
            ?.call) {
            try {
                const res = await this.$wire.call(this.searchMethod, this.search);
                const remoteResults = Array.isArray(res) ? res : [];
                const merged = [...staticResults];
                for (const r of remoteResults) {
                    if (!merged.find(o => o[this.valueField] == r[this
                            .valueField])) {
                        merged.push(r);
                    }
                }
                this.results = merged;
            } catch (e) {
                console.error('search error', e);
                this.results = staticResults;
            }
        } else {
            this.results = staticResults;
        }

        this.activeIndex = -1;
    },

    select(item) {
        if (this.multiple) {
            if (!this.selected.find(i => i[this.valueField] == item[this.valueField])) {
                this.selected.push(item);
            }
        } else {
            this.selected = [item];
            this.$refs.box?.focus();
        }
        this.search = '';
        this.results = [];
        this.open = false;
        this.syncWithWire();
    },

    createNew() {
        if (!this.allowCreate || !this.search.trim()) return;

        const newItem = {
            [this.valueField]: this.search.trim(),
            [this.labelField]: this.search.trim()
        };

        this.staticOptions.push(newItem);
        this.select(newItem);
    },

    handleKeydown(e) {
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            this.activeIndex = Math.min(this.activeIndex + 1, this.results.length - 1);
            return;
        }
        if (e.key === 'ArrowUp') {
            e.preventDefault();
            this.activeIndex = Math.max(this.activeIndex - 1, 0);
            return;
        }
        if (e.key === 'Enter' && this.activeIndex >= 0 && this.results[this.activeIndex]) {
            e.preventDefault();
            this.select(this.results[this.activeIndex]);
            return;
        }
        if (e.key === 'Enter' && this.allowCreate && this.search.trim() && this.results
            .length === 0) {
            e.preventDefault();
            this.createNew();
        }
        if (e.key === 'Escape') {
            this.open = false;
            this.$refs.box?.focus();
        }
    },

    remove(index) {
        this.selected.splice(index, 1);
        this.syncWithWire();
    },

    clearSelection() {
        this.selected = [];
        this.syncWithWire();
        this.focusInput();
    },

    syncWithWire() {
        if (!this.$wire) return;

        const ids = this.selected.map(i => i[this.valueField]);

        if (!this._initialized) {
            this._initialized = true;
            if (ids.length === 0) return;
        }

        this.$wire.set(this.wireModel, this.multiple ? ids : (ids.length > 0 ? ids[0] :
                null), this
            .isLive);
    },
}));

// <x-synapse-confirm-dialog>
Alpine.data('synapseConfirmDialog', (defaults) => ({
    show: false,
    defaults,
    title: defaults.title,
    message: defaults.message,
    confirmText: defaults.confirmText,
    cancelText: defaults.cancelText,
    confirmColor: defaults.confirmColor,
    icon: defaults.icon,
    onConfirm: null,
    onCancel: null,
    wireMethod: null,
    wireParams: null,
    wireComponent: null,
    open(data) {
        this.title = data.title || this.defaults.title;
        this.message = data.message || this.defaults.message;
        this.confirmText = data.confirmText || this.defaults.confirmText;
        this.cancelText = data.cancelText || this.defaults.cancelText;
        this.confirmColor = data.confirmColor || this.defaults.confirmColor;
        this.icon = data.icon || this.defaults.icon;
        this.onConfirm = data.onConfirm || null;
        this.onCancel = data.onCancel || null;
        this.wireMethod = data.wireMethod || null;
        this.wireParams = data.wireParams || null;
        this.wireComponent = data.wireComponent || null;
        this.show = true;
    },
    close() {
        this.show = false;
    },
    confirm() {
        if (this.wireMethod && this.wireComponent) {
            const component = Livewire.find(this.wireComponent);
            if (component && typeof component[this.wireMethod] === 'function') {
                if (this.wireParams && Array.isArray(this.wireParams)) {
                    component[this.wireMethod](...this.wireParams);
                } else {
                    component[this.wireMethod]();
                }
            }
        } else if (this.onConfirm && typeof this.onConfirm === 'function') {
            this.onConfirm();
        }
    },
    cancel() {
        if (this.onCancel && typeof this.onCancel === 'function') {
            this.onCancel();
        }
        this.close();
    }
}));

// <x-synapse-datepicker>
// mode: 'date' (default) or 'month' — a year/month grid via flatpickr's monthSelect plugin,
// formatted 'Y-m' so the submitted value matches what <input type="month"> would send.
Alpine.data('synapseDatepicker', (initialValue, flatpickrOptions, mode = 'date') => ({
    flatpickrInstance: null,
    value: initialValue,
    init() {
        // Month defaults go first so a call-site :flatpickr dateFormat still wins.
        const config = Object.assign({}, mode === 'month' ? { dateFormat: 'Y-m' } : {}, flatpickrOptions, {
            defaultDate: this.value || null,
            onChange: (selectedDates, dateStr, instance) => {
                this.value = dateStr;
            }
        });

        if (mode === 'month') {
            // The plugin's dateFormat drives the input text while flatpickr's drives parsing,
            // so it is built from the effective format rather than a second hardcoded 'Y-m'.
            config.plugins = [monthSelectPlugin({
                shorthand: false,
                dateFormat: config.dateFormat,
                altFormat: config.dateFormat,
            })];
        }

        this.flatpickrInstance = flatpickr(this.$el, config);
        this.$watch('value', (val) => {
            if (this.flatpickrInstance && val !== this.flatpickrInstance.input
                .value) {
                this.flatpickrInstance.setDate(val || null, false);
            }
        });
    }
}));

// <x-synapse-currency raw>: the input shows "1.250.000,50" (',' decimal, '.' thousands) while the
// entangled wire:model value stays a plain decimal string "1250000.50", so Livewire actions need no parsing.
// currency-helpers:start
const synapseCurrencyParse = (text) => {
    let digits = String(text ?? '').trim();
    if (digits === '') return '';
    const negative = digits.startsWith('-');
    digits = digits.replace(/\./g, '').replace(',', '.').replace(/[^\d.]/g, '');
    if (digits.endsWith('.')) digits = digits.slice(0, -1);
    if (digits === '') return '';
    return (negative ? '-' : '') + digits;
};

const synapseCurrencyFormat = (raw, decimals) => {
    let value = String(raw ?? '').trim();
    if (value === '' || Number.isNaN(Number(value))) return '';
    const negative = value.startsWith('-');
    const [whole, fraction = ''] = value.replace('-', '').split('.');
    const grouped = (whole.replace(/^0+(?=\d)/, '') || '0').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    const cents = fraction.slice(0, decimals).replace(/0+$/, '');
    return (negative ? '-' : '') + grouped + (cents !== '' ? ',' + cents : '');
};
// currency-helpers:end

// The entangle is always deferred: entangling with .live would send a $set request on every keystroke.
// A live model (syncDelay set) is sent with one $commit once typing pauses, like wire:model.live.
Alpine.data('synapseCurrency', (value, decimals, syncDelay) => ({
    value: value,
    decimals: decimals,
    display: '',
    syncTimer: null,
    init() {
        this.display = synapseCurrencyFormat(this.value, this.decimals);
        this.$watch('display', (text) => {
            const raw = synapseCurrencyParse(text);
            if (raw === String(this.value ?? '')) return;
            this.value = raw;
            if (syncDelay === null) return;
            clearTimeout(this.syncTimer);
            this.syncTimer = setTimeout(() => this.$wire.$commit(), syncDelay);
        });
        // Server-side changes (reset after save, a value set by an action): show them formatted.
        this.$watch('value', (raw) => {
            const shown = synapseCurrencyParse(this.display);
            const same = shown === '' ? String(raw ?? '') === '' : Number(shown) === Number(raw);
            if (!same) this.display = synapseCurrencyFormat(raw, this.decimals);
        });
    },
}));

// <x-synapse-drag-n-drop>
Alpine.data('synapseDragDrop', (config) => ({
    fileName: '',
    fileSize: '',
    previewUrl: null,
    isDragging: false,
    error: null,
    accept: config.accept,
    acceptExtensions: config.acceptExtensions,
    maxSize: config.maxSize,
    maxWidth: config.maxWidth,
    maxHeight: config.maxHeight,
    wireModel: config.wireModel,

    init() {
        // Watch for wire:model changes to clear preview
        if (this.wireModel) {
            this.$watch('$wire.' + this.wireModel, (value) => {
                if (value === null || value === '') {
                    // Only clear the UI, don't touch the file input during upload
                    this.fileName = '';
                    this.fileSize = '';
                    this.previewUrl = null;
                }
            });
        }
    },

    handleFiles(files) {
        if (files.length === 0) return;

        const file = files[0];
        this.error = null;

        if (this.accept && !this.accept.split(',').some(type => file.type === type
                .trim())) {
            this.error = 'Invalid file type. Accepted types: ' +
                this.acceptExtensions;
            this.clearFileInput();
            return;
        }

        const fileSizeKB = Math.round(file.size / 1024);
        if (this.maxSize && fileSizeKB > this.maxSize) {
            this.error =
                `File size exceeds maximum allowed size of ${this.maxSize}KB (${Math.round(this.maxSize / 1024)}MB)`;
            this.clearFileInput();
            return;
        }

        this.setFileInfo(file);
    },

    setFileInfo(file) {
        this.fileName = file.name;
        this.fileSize = this.formatFileSize(file.size);

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                this.previewUrl = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    },

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    },

    clearFileInput() {
        if (this.$refs.fileInput) {
            this.$refs.fileInput.value = '';
        }

        if (this.wireModel) {
            this.$wire.set(this.wireModel, null);
        }
    },

    removeFile() {
        this.fileName = '';
        this.fileSize = '';
        this.previewUrl = null;
        this.error = null;
        this.clearFileInput();
    },

    triggerFileInput() {
        if (this.$refs.fileInput) {
            this.$refs.fileInput.click();
        }
    },

    onDrop(event) {
        this.isDragging = false;
        if (this.wireModel) {
            this.handleDrop(event);
        } else {
            this.handleFiles(event.dataTransfer.files);
        }
    },

    handleDrop(event) {
        const files = event.dataTransfer.files;

        if (files.length > 0 && this.$refs.fileInput) {
            this.$refs.fileInput.files = files;
            const changeEvent = new Event('change', {
                bubbles: true
            });
            this.$refs.fileInput.dispatchEvent(changeEvent);
        }
    },

    handleLivewireUploadStart() {
        this.error = null;

        if (this.$refs.fileInput && this.$refs.fileInput.files.length > 0) {
            const file = this.$refs.fileInput.files[0];
            this.fileName = file.name;
            this.fileSize = this.formatFileSize(file.size);

            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.previewUrl = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }
    },

    handleLivewireUploadFinish(event) {
        // Upload completed successfully
    },

    handleLivewireUploadError() {
        this.error = 'Upload failed. Please try again.';
        this.removeFile();
    }
}));

// <x-synapse-file-drop>
Alpine.data('synapseFileDrop', () => ({
    isDragging: false,
    handleDrop(e) {
        this.isDragging = false;
        const input = this.$refs.input;
        if (!input) return;
        const files = e.dataTransfer.files;
        if (!files || files.length === 0) return;
        input.files = files;
        input.dispatchEvent(new Event('change', {
            bubbles: true
        }));
    }
}));

// <x-synapse-lightbox>
Alpine.data('synapseLightbox', () => ({
    show: false,
    url: '',
    alt: '',
    open(detail) {
        this.url = detail?.url ?? '';
        this.alt = detail?.alt ?? '';
        this.show = true;
    },
    close() {
        this.show = false;
    },
}));

// <x-synapse-modal>
// Tab order inside a dialog. Module scope, not an inline attribute: the CSP
// Alpine build evaluates attributes without access to globals or closures.
const SYNAPSE_FOCUSABLE = [
    'a[href]', 'button:not([disabled])', 'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])', 'textarea:not([disabled])', '[tabindex]:not([tabindex="-1"])',
].join(',');

/**
 * <x-synapse-drawer name="…">: a named drawer owns its `drawerOpen` (shadowing any
 * parent's) and is opened/closed by the open-drawer-{name} / close-drawer-{name}
 * window events bound in the view.
 */
Alpine.data('synapseDrawer', () => ({
    drawerOpen: false,
}));

Alpine.data('synapseModal', (name) => ({
    show: false,
    trigger: null,

    open() {
        // Remember who opened it, so focus can go back there on close.
        this.trigger = document.activeElement;
        this.show = true;
        // HTML autofocus only fires on page load, and CSP attributes can't hold
        // a $nextTick callback, so the first field is focused here.
        this.$nextTick(() => this.focusFirst());
    },

    focusFirst() {
        const panel = this.$refs.panel;
        if (!panel) return;
        const target = panel.querySelector('[autofocus]')
            || panel.querySelector(SYNAPSE_FOCUSABLE);
        target?.focus();
    },

    // A modal's content can arrive late (a child Livewire component), so the
    // list is read on each Tab rather than cached at open().
    trapTab(event) {
        const panel = this.$refs.panel;
        if (!panel) return;
        const items = [...panel.querySelectorAll(SYNAPSE_FOCUSABLE)]
            .filter(el => el.offsetParent !== null);
        if (items.length === 0) return;

        const first = items[0];
        const last = items[items.length - 1];
        const current = document.activeElement;

        if (event.shiftKey) {
            (current === first || !panel.contains(current) ? last : items[items.indexOf(current) - 1] || last).focus();
            return;
        }
        (current === last || !panel.contains(current) ? first : items[items.indexOf(current) + 1] || first).focus();
    },

    close() {
        if (!this.show) return;
        this.show = false;
        this.$dispatch('closed-modal-' + name);
        // Focus the trigger only if it is still in the document.
        if (this.trigger && this.trigger.isConnected) {
            this.trigger.focus();
        }
        this.trigger = null;
    },
}));

// <x-synapse-password-generator>
Alpine.data('synapsePasswordGenerator', (initialLength, models) => ({
    showPassword: false,
    passwordLength: initialLength,

    generatePassword(length) {
        const lowercase = 'abcdefghijklmnopqrstuvwxyz';
        const uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        const numbers = '0123456789';
        const symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';
        const allChars = lowercase + uppercase + numbers + symbols;

        let password = [
            lowercase[Math.floor(Math.random() * lowercase.length)],
            uppercase[Math.floor(Math.random() * uppercase.length)],
            numbers[Math.floor(Math.random() * numbers.length)],
            symbols[Math.floor(Math.random() * symbols.length)]
        ];

        for (let i = password.length; i < length; i++) {
            password.push(allChars[Math.floor(Math.random() * allChars.length)]);
        }

        for (let i = password.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            [password[i], password[j]] = [password[j], password[i]];
        }

        return password.join('');
    },

    generate() {
        const newPassword = this.generatePassword(this.passwordLength);
        this.$refs.passwordInput.value = newPassword;
        this.$refs.passwordInput.dispatchEvent(new Event('input'));
    },

    setProperty() {
        const password = this.$refs.passwordInput.value;
        if (password) {
            (models || []).forEach((property) => this.$wire.set(property, password));
            this.$dispatch('password-set');
        }
    },

    async copyPassword() {
        const password = this.$refs.passwordInput.value;
        if (password) {
            try {
                await navigator.clipboard.writeText(password);
                this.$dispatch('password-copied');
            } catch (err) {
                console.error('Failed to copy password:', err);
            }
        }
    }
}));

// synsearch::palette (Livewire)
Alpine.data('synsearchPalette', () => ({
    active: 0,
    init() {
        // `show` is the enclosing synapse modal's Alpine state.
        this.$watch('show', (open) => {
            if (open) {
                this.$nextTick(() => this.$refs.input.focus());
            }
        });
    },
    count() {
        return this.$refs.list ? this.$refs.list.children.length : 0;
    },
    next() {
        this.active = Math.min(this.active + 1, Math.max(this.count() - 1, 0));
    },
    prev() {
        this.active = Math.max(this.active - 1, 0);
    },
    reset() {
        this.active = 0;
    },
    openActive() {
        const item = this.$refs.list ? this.$refs.list.children[this.active] : null;
        const link = item ? item.querySelector('a') : null;

        if (link) {
            link.click();
        }
    },
}));

// <x-synapse-toastr>
Alpine.data('toastrNotification', (notification, displayDuration, removeNotification) => ({
    isVisible: false,
    timeout: null,
    init() {
        this.$nextTick(() => {
            this.isVisible = true;
        });
        this.timeout = setTimeout(() => {
            this.isVisible = false;
            removeNotification(notification.id);
        }, displayDuration);
    },
    pause() {
        clearTimeout(this.timeout);
    },
    resume() {
        this.timeout = setTimeout(() => {
            this.isVisible = false;
            removeNotification(notification.id);
        }, displayDuration);
    },
    dismiss() {
        this.isVisible = false;
        removeNotification(notification.id);
    },
    dismissDelayed(delay = 400) {
        this.isVisible = false;
        setTimeout(() => removeNotification(notification.id), delay);
    },
}));

Alpine.data('toastrRoot', (flashNotifications) => ({
    notifications: [],
    displayDuration: 8000,
    soundEffect: false,

    init() {
        // Children call removeNotification() without a receiver; under the CSP
        // evaluator `this` would be the merged scope, where any Alpine.data()
        // registered as "notifications" shadows this component's array.
        this.removeNotification = this.removeNotification.bind(this);
    },

    initFlashNotifications() {
        flashNotifications.forEach((notification) => this.addNotification(
            notification));
    },

    handleNotifyEvent(event) {
        const detail = Array.isArray(event.detail) ? event.detail[0] : event.detail;
        this.addNotification({
            variant: detail.variant,
            sender: detail.sender,
            title: detail.title,
            message: detail.message,
        });
    },

    addNotification({
        variant = 'info',
        sender = null,
        title = null,
        message = null
    }) {
        const id = Date.now()
        const notification = {
            id,
            variant,
            sender,
            title,
            message
        }

        // Keep only the most recent 20 notifications
        if (this.notifications.length >= 20) {
            this.notifications.splice(0, this.notifications.length - 19)
        }

        // Add the new notification to the notifications stack
        this.notifications.push(notification)

        if (this.soundEffect) {
            // Play the notification sound
            const notificationSound = new Audio(
                'https://res.cloudinary.com/ds8pgw1pf/video/upload/v1728571480/penguinui/component-assets/sounds/ding.mp3'
            )
            notificationSound.play().catch((error) => {
                console.error('Error playing the sound:', error)
            })
        }
    },
    removeNotification(id) {
        setTimeout(() => {
            this.notifications = this.notifications.filter(
                (notification) => notification.id !== id,
            )
        }, 400);
    },
}));

// <x-synapse-toggler>
Alpine.data('synapseTogglerInline', (checkedValue) => ({
    active: checkedValue,
    init() {
        this.active = this.$el.dataset.checked === 'true';
        new MutationObserver(() => {
            this.active = this.$el.dataset.checked === 'true';
        }).observe(this.$el, {
            attributes: true,
            attributeFilter: ['data-checked']
        });
    }
}));

Alpine.data('synapseToggler', (initialActive) => ({
    active: initialActive,
}));

// <x-synapse-color-picker>
Alpine.data('synapseColorPicker', (initialValue) => ({
    value: initialValue,
    isValidHex() {
        return /^#[0-9a-fA-F]{6}$/.test(this.value || '');
    },
    nativeValue() {
        return this.isValidHex() ? this.value.toLowerCase() : '#000000';
    },
    fromNative(event) {
        this.value = event.target.value;
    },
    pick(color) {
        this.value = color;
    },
    isSelected(color) {
        return (this.value || '').toLowerCase() === color.toLowerCase();
    },
}));

// <x-synapse-split-pane>
Alpine.data('synapseSplitPane', (storageKey, initialWidth) => ({
    width: initialWidth,
    asideOpen: false,
    init() {
        if (!storageKey) return;
        try {
            const saved = window.localStorage.getItem('syn-split:' + storageKey);
            if (saved) this.width = saved;
        } catch (e) {
            // Storage blocked (private mode, sandbox): keep the default width.
        }
    },
    gridStyle() {
        return '--syn-split-aside: ' + this.width;
    },
    toggleAside() {
        this.asideOpen = !this.asideOpen;
    },
    startDrag() {
        const move = (event) => {
            const rect = this.$el.getBoundingClientRect();
            const px = Math.min(Math.max(event.clientX - rect.left, 160), rect.width - 240);
            this.width = px + 'px';
        };
        const stop = () => {
            window.removeEventListener('pointermove', move);
            window.removeEventListener('pointerup', stop);
            this.persist();
        };
        window.addEventListener('pointermove', move);
        window.addEventListener('pointerup', stop);
    },
    persist() {
        if (!storageKey) return;
        try {
            window.localStorage.setItem('syn-split:' + storageKey, this.width);
        } catch (e) {
            // Storage blocked: the width just isn't remembered.
        }
    },
}));

// <x-synapse-copy-button>
Alpine.data('synapseCopyButton', (text, labels) => ({
    copied: false,
    failed: false,
    timer: null,
    async copy() {
        const ok = await this.write(text);
        this.copied = ok;
        this.failed = !ok;
        clearTimeout(this.timer);
        this.timer = setTimeout(() => {
            this.copied = false;
            this.failed = false;
        }, 2000);
    },
    async write(value) {
        try {
            if (navigator.clipboard && window.isSecureContext) {
                await navigator.clipboard.writeText(value);
                return true;
            }
        } catch (e) {
            // Permission denied or unsupported: try the legacy path below.
        }

        const area = document.createElement('textarea');
        area.value = value;
        area.setAttribute('readonly', '');
        area.style.position = 'fixed';
        area.style.opacity = '0';
        document.body.appendChild(area);
        area.select();

        let ok = false;
        try {
            ok = document.execCommand('copy');
        } catch (e) {
            ok = false;
        }
        document.body.removeChild(area);

        return ok;
    },
    isIdle() {
        return !this.copied && !this.failed;
    },
    buttonText() {
        if (this.copied) return labels.copied;
        if (this.failed) return labels.failed;
        return labels.label;
    },
    announcement() {
        if (this.copied) return labels.copied;
        if (this.failed) return labels.failed;
        return '';
    },
}));

// RemembersQueryParams: when a list component restores filters from the session,
// Livewire's #[Url] tracking never writes them to the address bar. Merge them in so
// the URL matches what's on screen (and can be shared/bookmarked).
window.addEventListener("synapse-restored-query", (event) => {
    const detail = Array.isArray(event.detail) ? event.detail[0] : event.detail;
    const query = detail && detail.query;
    if (!query) return;

    const url = new URL(window.location.href);
    Object.entries(query).forEach(([key, value]) => url.searchParams.set(key, value));
    window.history.replaceState(window.history.state, "", url);
});

/**
 * Global loading overlay (<x-synapse-loader>): on while wire:navigate runs or a
 * Livewire request is in flight, off when it settles either way.
 */
Alpine.data('synapseLoader', () => ({
    loading: false,

    init() {
        // livewire:navigate fires on the click, before the page is fetched (livewire:navigating
        // only fires once the fetch is done). Another listener may cancel it, so check after
        // every listener has run and stand down if the navigation is not happening.
        document.addEventListener('livewire:navigate', (event) => {
            this.loading = true;
            setTimeout(() => {
                if (event.defaultPrevented) {
                    this.loading = false;
                }
            });
        });
        document.addEventListener('livewire:navigated', () => {
            this.loading = false;
        });

        Livewire.interceptMessage(({ onSuccess, onError, onFailure }) => {
            this.loading = true;
            const done = () => {
                this.loading = false;
            };
            onSuccess(done);
            onError(done);
            onFailure(done);
        });
    },
}));
