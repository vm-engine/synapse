<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class ColorPicker extends Component
{
    private const HEX_PATTERN = '/^#[0-9a-fA-F]{6}$/';

    /**
     * @param  array<int, mixed>  $swatches  Preset colors; anything that is not #rrggbb is dropped.
     */
    public function __construct(
        public array $swatches = [],
        public bool $allowCustom = true,
    ) {
        $this->swatches = array_values(array_filter(
            $swatches,
            fn (mixed $color): bool => is_string($color) && preg_match(self::HEX_PATTERN, $color) === 1,
        ));

        if ($this->swatches === []) {
            $this->allowCustom = true;
        }
    }

    public function render()
    {
        return view('synapse::components.color-picker');
    }
}
