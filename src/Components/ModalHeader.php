<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;
use VmEngine\Synapse\Components\Concerns\SanitizesWireArguments;

/**
 * Header drawn by a modal's child form, whose title depends on the child's state.
 * Pair it with <x-synapse-modal name="x" labelledby="modal-x-title">.
 */
class ModalHeader extends Component
{
    use SanitizesWireArguments;

    public string $name;

    public function __construct(string $modal, public string $title)
    {
        $this->name = $this->safeKey($modal) ?? '';
    }

    public function render()
    {
        return view('synapse::components.modal-header');
    }
}
