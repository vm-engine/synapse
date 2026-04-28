<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

/**
 * Multi-file drop zone with drag-and-drop, paired with Livewire's wire:model.
 *
 * Designed for use cases where one form has several "slots", each accepting
 * one or more files of arbitrary types. Unlike <x-synapse-drag-n-drop> (which
 * is single-image-only with preview), this component:
 *
 *   - Supports `multiple` files per slot
 *   - Accepts any file type via `allowedTypes` (extension list)
 *   - Enforces a `maxCount` (combined with `currentCount` from the parent)
 *   - Stays presentation-agnostic — no embedded image preview
 *
 * Default strings can be sourced from a consumer module's lang namespace by
 * passing `module="my-module"`; the component then looks up keys under
 * `my-module::file_drop.{label,full,uploading,hint}` with English fallback.
 *
 * The consumer renders existing/already-uploaded files separately above or
 * below the component; FileDrop only owns the drop zone + new-file pickup.
 */
class FileDrop extends Component
{
    /**
     * @param  array<int, string>  $allowedTypes  File extensions without the leading dot, e.g. ['pdf', 'jpg']
     * @param  string|null  $module  Lang namespace for default strings — when set, defaults resolve via
     *                               __("{$module}::file_drop.{key}") with English fallback if the key
     *                               doesn't exist.
     */
    public function __construct(
        public ?string $wireModel = null,
        public bool $multiple = false,
        public ?int $maxCount = null,
        public ?int $maxSizeKb = null,
        public array $allowedTypes = [],
        public ?string $label = null,
        public ?string $hint = null,
        public int $currentCount = 0,
        public string $name = 'file',
        public ?string $error = null,
        public ?string $fullText = null,
        public ?string $uploadingText = null,
        public ?string $module = null,
    ) {}

    public function render()
    {
        return view('synapse::components.file-drop');
    }

    public function isFull(): bool
    {
        return $this->maxCount !== null && $this->currentCount >= $this->maxCount;
    }

    public function resolvedLabel(): string
    {
        return $this->label ?? $this->trans('label', 'Click to upload or drag &amp; drop');
    }

    public function resolvedFullText(): string
    {
        return $this->fullText ?? $this->trans('full', 'Maximum file reached');
    }

    public function resolvedUploadingText(): string
    {
        return $this->uploadingText ?? $this->trans('uploading', 'Uploading…');
    }

    public function resolvedHint(): string
    {
        if ($this->hint !== null) {
            return $this->hint;
        }

        $parts = [];
        if (! empty($this->allowedTypes)) {
            $parts[] = strtoupper(implode(', ', $this->allowedTypes));
        }
        if ($this->maxSizeKb) {
            $maxLabel = $this->trans('max', 'max');
            $kbLabel = $this->trans('kb', 'KB');
            $parts[] = $maxLabel.' '.number_format($this->maxSizeKb).' '.$kbLabel;
        }

        return $parts ? implode(' · ', $parts) : '';
    }

    public function acceptString(): string
    {
        if (empty($this->allowedTypes)) {
            return '';
        }

        return '.'.implode(',.', $this->allowedTypes);
    }

    public function counterText(): ?string
    {
        if ($this->maxCount === null) {
            return null;
        }

        return $this->currentCount.'/'.$this->maxCount;
    }

    /**
     * Resolve a translation key from the consumer module's `file_drop` lang
     * namespace, falling back to a hardcoded English default when the module
     * is not provided or the key isn't defined.
     */
    private function trans(string $key, string $fallback): string
    {
        if (! $this->module) {
            return $fallback;
        }

        $full = $this->module.'::file_drop.'.$key;
        $value = __($full);

        return is_string($value) && $value !== $full ? $value : $fallback;
    }
}
