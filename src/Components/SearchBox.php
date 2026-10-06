<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class SearchBox extends Component
{
    public function __construct(
        public ?string $placeholder = null,
    ) {}

    public function render()
    {
        return view('synapse::components.search-box');
    }
}
