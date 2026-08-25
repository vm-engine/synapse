<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class AdvSelect extends Component
{
    /**
     * @param  array<int|string, mixed>|null  $preload
     * @param  array<int|string, mixed>  $options
     */
    public function __construct(
        public string $wireModel,
        public ?string $searchMethod = null,
        public ?string $loadMethod = null,
        public bool $multiple = false,
        public string $placeholder = 'Select an option',
        public string $labelField = 'label',
        public string $valueField = 'value',
        public ?array $preload = null,
        public array $options = [],
        public int $minSearchLength = 2,
        public bool $live = false,
        public bool $allowCreate = false,
        public bool $autoLoad = true,
    ) {}

    public function render()
    {
        return view('synapse::components.adv-select');
    }
}
