<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class SortIcon extends Component
{
    public function __construct(
        public string $field,
        public string $sortField,
        public string $sortDirection,
    ) {}

    public function render()
    {
        return view('synapse::components.sort-icon');
    }
}
