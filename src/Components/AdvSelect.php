<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class AdvSelect extends Component
{
    /** Placeholder text (default: "Select an option", translated). */
    public string $placeholder;

    /**
     * @param  array<int|string, mixed>|null  $preload
     * @param  array<int|string, mixed>  $options
     */
    public function __construct(
        public string $wireModel,
        public ?string $searchMethod = null,
        public ?string $loadMethod = null,
        public bool $multiple = false,
        ?string $placeholder = null,
        public string $labelField = 'label',
        public string $valueField = 'value',
        public ?array $preload = null,
        public array $options = [],
        public int $minSearchLength = 2,
        public bool $live = false,
        public bool $allowCreate = false,
        public bool $autoLoad = true,
        public bool $disabled = false,
        /** Accessible name for the dropdown's filter input (default: "Search"). */
        public ?string $searchLabel = null,
    ) {
        $this->placeholder = $placeholder ?? __('Select an option');
    }

    public function render()
    {
        return view('synapse::components.adv-select');
    }
}
