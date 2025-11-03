<?php

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class AdvSelect extends Component
{
    public function __construct(
        public $wireModel,
        public $searchMethod = null,
        public $multiple = false,
        public $placeholder = 'Select an option',
        public $labelField = 'label',
        public $valueField = 'value',
        public $preload = null,
        public $options = [],
        public $minSearchLength = 2,
        public $live = false,
    ) {}

    public function render()
    {
        return view('synapse::components.adv-select');
    }
}
