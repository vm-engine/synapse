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
        public string $placeholder = 'Select option',
        public string $searchPlaceholder = 'Search...',
        public string $config = '{}',
        public string $modelName = '',
    ) {}

    public function render()
    {
        return view('synapse::components.select2');
    }
}
