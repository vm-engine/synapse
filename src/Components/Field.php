<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;

/** A form-box row: label, wire-bound input (or the slot's own control) and its error/hint, linked for screen readers. */
class Field extends Component
{
    public string $id;

    /** Validation error key; the model path unless the form validates under another key. */
    public string $error;

    /**
     * Aria attributes that tie the control to this field's label, hint and error.
     *
     * The built-in branches render them on their own control. A slotted control gets
     * them without the caller spelling them out: Synapse's own slottable controls
     * (adv-select, datepicker) read this through `@aware(['fieldAria' => []])`. Raw HTML in
     * a slot cannot — Blade has no way to reach into slot markup — so such a slot
     * still writes aria-describedby="{id}-error"/"{id}-hint" and aria-invalid itself.
     *
     * @var array<string, string>
     */
    public array $fieldAria;

    public function __construct(
        public string $name,
        public string $label,
        public string $type = 'text',
        public bool $required = false,
        public bool $disabled = false,
        public ?string $hint = null,
        public ?string $step = null,
        public ?string $inputmode = null,
        public ?string $autocomplete = null,
        public string $modifier = '',
        /** Classes for the control itself, e.g. 'text-right'; `class` styles the form-box instead. */
        public string $inputClass = '',
        /** Rows of a `type="textarea"` field. */
        public string $rows = '3',
        public ?string $prefix = null,
        public int $decimals = 0,
        ?string $id = null,
        ?string $error = null,
    ) {
        $this->id = $id ?? 'f-'.str_replace('.', '-', $name);
        $this->error = $error ?? $name;

        $errors = View::shared('errors') ?? new ViewErrorBag;
        $hasError = $errors->has($this->error);
        $describedBy = trim(($hasError ? $this->id.'-error ' : '').($hint !== null && $hint !== '' ? $this->id.'-hint' : ''));

        $this->fieldAria = array_filter([
            'aria-labelledby' => $this->id.'-label',
            'aria-describedby' => $describedBy !== '' ? $describedBy : null,
            'aria-invalid' => $hasError ? 'true' : null,
            'aria-required' => $required ? 'true' : null,
        ]);
    }

    public function render()
    {
        return view('synapse::components.field');
    }
}
