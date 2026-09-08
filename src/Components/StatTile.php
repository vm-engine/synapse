<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class StatTile extends Component
{
    /** @var array<int, string> */
    private const COLORS = ['gray', 'blue', 'indigo', 'purple', 'red', 'orange', 'green'];

    /** @var array<int, string> */
    private const VARIANTS = ['tile', 'card'];

    /** @var array<int, string> */
    private const ICON_POSITIONS = ['left', 'title', 'background'];

    public function __construct(
        public ?string $icon = null,
        public string $color = 'gray',
        public ?string $label = null,
        public ?string $value = null,
        public ?string $valueColor = null,
        public bool $bordered = true,
        public string $variant = 'tile',
        public string $iconPosition = 'left',
        public bool $iconBackground = true,
        public bool $tinted = false,
        public ?string $sublabel = null,
        public ?string $sublabelColor = null,
        public ?string $href = null,
        public bool $clickable = false,
    ) {
        if (! in_array($this->color, self::COLORS, true)) {
            $this->color = 'gray';
        }

        if ($this->valueColor !== null && ! in_array($this->valueColor, self::COLORS, true)) {
            $this->valueColor = null;
        }

        if ($this->sublabelColor !== null && ! in_array($this->sublabelColor, self::COLORS, true)) {
            $this->sublabelColor = null;
        }

        if (! in_array($this->variant, self::VARIANTS, true)) {
            $this->variant = 'tile';
        }

        if (! in_array($this->iconPosition, self::ICON_POSITIONS, true)) {
            $this->iconPosition = 'left';
        }
    }

    public function render()
    {
        return view('synapse::components.stat-tile');
    }
}
