<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class ConfirmDialog extends Component
{
    public function __construct(
        public ?string $title = 'Confirm Action',
        public ?string $message = 'Are you sure you want to proceed?',
        public ?string $confirmText = 'Confirm',
        public ?string $cancelText = 'Cancel',
        public ?string $confirmColor = 'danger',
        public ?string $icon = 'fa-solid fa-circle-exclamation',
    ) {}

    public function render()
    {
        return view('synapse::components.confirm-dialog');
    }
}
