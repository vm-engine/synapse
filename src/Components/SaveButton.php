<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;
use VmEngine\Synapse\Components\Concerns\SanitizesWireArguments;

/** Primary save button with a spinner + "Saving…" while its Livewire action runs. */
class SaveButton extends Component
{
    use SanitizesWireArguments;

    public string $safeAction;

    public function __construct(
        string $action = 'save',
        public ?string $label = null,
        public string $icon = 'ph ph-floppy-disk',
        public ?string $target = null,
        public bool $submit = false,
    ) {
        $this->safeAction = $this->wireMethod($action) ?? 'save';
    }

    /** wire:target value: the action plus any extra targets (e.g. a file upload property). */
    public function loadingTarget(): string
    {
        return $this->target === null ? $this->safeAction : $this->safeAction.', '.$this->target;
    }

    public function render()
    {
        return view('synapse::components.save-button');
    }
}
