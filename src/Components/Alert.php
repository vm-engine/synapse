<?php

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class Alert extends Component
{
    public function __construct(
        public ?string $type = 'info',
        public ?string $title = null,
        public ?string $message = null,
        public bool $dismissible = true,
        public ?string $icon = null,
        public bool $border = false,
        public bool $solid = false,
    ) {
        // Auto-assign icons based on type if not provided
        if ($this->icon === null) {
            $this->icon = match ($this->type) {
                'success' => 'fa-solid fa-circle-check',
                'warning' => 'fa-solid fa-triangle-exclamation',
                'error', 'danger' => 'fa-solid fa-circle-exclamation',
                'info' => 'fa-solid fa-circle-info',
                default => 'fa-solid fa-circle-info',
            };
        }

        // Normalize type (error -> danger)
        if ($this->type === 'error') {
            $this->type = 'danger';
        }
    }

    public function render()
    {
        return view('synapse::components.alert');
    }
}
