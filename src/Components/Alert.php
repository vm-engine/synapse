<?php

declare(strict_types=1);

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
                'success' => 'ph ph-check-circle',
                'warning' => 'ph ph-warning',
                'error', 'danger' => 'ph ph-warning-circle',
                'info' => 'ph ph-info',
                default => 'ph ph-info',
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
