{{--
    Lightbox Component

    Full-screen image preview on a dark backdrop. Opens on the window event
    `open-lightbox-{name}` with detail `{ url, alt? }`; closes on backdrop
    click, the close button, Escape, or `close-lightbox-{name}`.

    Props:
    - name (string): Event scope, default "lightbox"

    Usage:
    <x-synapse-lightbox name="fm-preview" />
    <button @click="$dispatch('open-lightbox-fm-preview', { url: '/img.jpg' })">Preview</button>
--}}

<div
    style="display: none;"
    x-data="synapseLightbox()"
    @open-lightbox-{{ $name }}.window="open($event.detail)"
    @close-lightbox-{{ $name }}.window="close()"
    @keydown.escape.window="show && close()"
    @click="close()"
    x-show="show"
    x-cloak
    x-transition
    {{ $attributes->merge(['class' => 'syn-lightbox']) }}
>
    <div
        class="syn-lightbox-frame"
        @click.stop
    >
        <button
            class="syn-lightbox-close"
            type="button"
            @click="close()"
        >
            <span class="ph ph-x"></span>
            <span class="sr-only">Close</span>
        </button>
        <img
            class="syn-lightbox-img"
            :src="url"
            :alt="alt"
        >
    </div>
</div>

<script nonce="{{ csp_nonce() }}">
    (() => {
        const register = () => {
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
        };
        document.addEventListener('alpine:init', register);
        if (window.Alpine) {
            register();
        }
    })();
</script>
