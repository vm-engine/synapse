<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

/** A form-box row: label, wire-bound input (or the slot's own control) and its error/hint, linked for screen readers. */
class Field extends Component
{
    public string $id;

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
        ?string $id = null,
    ) {
        $this->id = $id ?? 'f-'.str_replace('.', '-', $name);
    }

    public function render()
    {
        return view('synapse::components.field');
    }
}
