<?php

namespace VmEngine\Synapse\Components;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\Component;

class PerPageSelector extends Component
{
    public function __construct(
        public LengthAwarePaginator $paginator,
    ) {}

    public function render()
    {
        return view('synapse::components.per-page-selector');
    }
}
