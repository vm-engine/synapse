<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;
use VmEngine\Synapse\Components\Concerns\SanitizesWireArguments;

class SplitPane extends Component
{
    use SanitizesWireArguments;

    private const DEFAULT_WIDTH = '20rem';

    public ?string $storageKey;

    /**
     * @param  string  $asideWidth  Initial aside width: a number + px|rem|% (default 20rem)
     * @param  bool  $resizable  Show a drag handle; the width is remembered per browser when storageKey is set
     * @param  string|null  $storageKey  localStorage key suffix for the remembered width
     * @param  string  $toggleLabel  Label of the mobile show/hide-aside button
     */
    public function __construct(
        public string $asideWidth = self::DEFAULT_WIDTH,
        public bool $resizable = false,
        ?string $storageKey = null,
        public string $toggleLabel = 'Toggle panel',
    ) {
        if (preg_match('/^\d+(\.\d+)?(px|rem|%)$/', $asideWidth) !== 1) {
            $this->asideWidth = self::DEFAULT_WIDTH;
        }

        $this->storageKey = $this->safeKey($storageKey);
    }

    public function render()
    {
        return view('synapse::components.split-pane');
    }
}
