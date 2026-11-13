<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;
use VmEngine\Synapse\Components\Concerns\SanitizesWireArguments;

/** Footer drawn by a modal's child form: Cancel (closes the modal) + save button, or the slot instead of save. */
class ModalFooter extends Component
{
    use SanitizesWireArguments;

    public string $name;

    public function __construct(
        string $modal,
        public string $action = 'save',
        public ?string $saveLabel = null,
    ) {
        $this->name = $this->safeKey($modal) ?? '';
    }

    public function render()
    {
        return view('synapse::components.modal-footer');
    }
}
