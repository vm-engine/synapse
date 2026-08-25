<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\Component;

class PerPageSelector extends Component
{
    /** @param  LengthAwarePaginator<int, mixed>  $paginator */
    public function __construct(
        public LengthAwarePaginator $paginator,
    ) {}

    public function render()
    {
        return view('synapse::components.per-page-selector');
    }
}
