<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class RadioButtons extends Component
{
    /** @param  array<int|string, mixed>  $options */
    public function __construct(
        public string $name,
        public array $options,
        public string $model = '',
    ) {}

    public function render()
    {
        return view('synapse::components.radio-buttons');
    }
}
