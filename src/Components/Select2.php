<?php

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class Select2 extends Component
{
    public function __construct(
        public string $name = '',
        public array $options = [],
        public mixed $model = null,
        public bool $multiple = false,
        public bool $searchable = true,
        public bool $ajax = false,
        public string $ajaxUrl = '',
        public string $placeholder = 'Select option',
        public string $searchPlaceholder = 'Search...',
    ) {}

    public function render()
    {
        return view('synapse::components.select2');
    }
}
