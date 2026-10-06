<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class Badge extends Component
{
    /** @var array<int, string> */
    private const COLORS = ['gray', 'brand', 'success', 'warning', 'danger', 'info'];

    /** @var array<int, string> */
    private const SIZES = ['sm', 'md'];

    public function __construct(
        public string $color = 'gray',
        public string $size = 'md',
        public ?string $icon = null,
    ) {
        if (! in_array($this->color, self::COLORS, true)) {
            $this->color = 'gray';
        }

        if (! in_array($this->size, self::SIZES, true)) {
            $this->size = 'md';
        }
    }

    public function render()
    {
        return view('synapse::components.badge');
    }
}
