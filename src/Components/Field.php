<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

/** A form-box row: label, wire-bound input (or the slot's own control) and its error/hint, linked for screen readers. */
class Field extends Component
{
    public string $id;

    /** Validation error key; the model path unless the form validates under another key. */
    public string $error;

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
        public ?string $prefix = null,
        public int $decimals = 0,
        ?string $id = null,
        ?string $error = null,
    ) {
        $this->id = $id ?? 'f-'.str_replace('.', '-', $name);
        $this->error = $error ?? $name;
    }

    public function render()
    {
        return view('synapse::components.field');
    }
}
