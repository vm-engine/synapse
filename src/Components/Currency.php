<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class Currency extends Component
{
    /**
     * @param  int  $decimals  Decimal places the mask allows (',' decimal, '.' thousands)
     * @param  bool  $raw  Send the plain number ("1250000.50") to wire:model instead of the formatted text
     */
    public function __construct(
        public string $currency = 'Rp.',
        public int $decimals = 0,
        public bool $raw = false,
    ) {}

    public function render()
    {
        return view('synapse::components.currency');
    }
}
