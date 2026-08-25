<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class Checkboxes extends Component
{
    /**
     * @param  array<int|string, mixed>  $options
     * @param  array<int, mixed>  $model
     */
    public function __construct(
        public string $name,
        public array $options,
        public array $model = [],
    ) {}

    public function render()
    {
        return view('synapse::components.checkboxes');
    }
}
