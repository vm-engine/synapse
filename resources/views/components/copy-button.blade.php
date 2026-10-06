{{--
    Copy Button Component

    Copies `text` to the clipboard (Clipboard API, falling back to a hidden
    textarea + execCommand on non-secure origins) and shows a check / warning
    for 2 seconds. Announces the result to screen readers.

    Props:
    - text (string): Text to copy (required)
    - label (string): Accessible name / visible label (default: 'Copy')
    - iconOnly (bool): Hide the visible label (default: true)

    Usage:
    <x-synapse-copy-button text="packages/synmod-example/src" label="Copy path" />
    <x-synapse-copy-button :text="$token" label="Copy token" :icon-only="false" />
--}}

<button
    type="button"
    title="{{ $label }}"
    aria-label="{{ $label }}"
    x-data="synapseCopyButton({{ json_encode($text) }}, {{ json_encode($labels()) }})"
    :class="{ 'syn-copy-button-copied': copied, 'syn-copy-button-failed': failed }"
    @click="copy()"
    {{ $attributes->merge(['class' => 'syn-copy-button']) }}
>
    <i
        class="ph ph-copy"
        x-show="isIdle()"
    ></i>
    <i
        class="ph ph-check"
        x-cloak
        x-show="copied"
    ></i>
    <i
        class="ph ph-warning"
        x-cloak
        x-show="failed"
    ></i>
    @unless ($iconOnly)
        <span
            class="syn-copy-button-text"
            x-text="buttonText()"
        >{{ $label }}</span>
    @endunless
    <span
        class="sr-only"
        aria-live="polite"
        x-text="announcement()"
    ></span>
</button>
