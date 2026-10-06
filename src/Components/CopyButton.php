<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class CopyButton extends Component
{
    /**
     * @param  string  $text  Text copied to the clipboard
     * @param  string  $label  Button label / accessible name (default: 'Copy')
     * @param  bool  $iconOnly  Hide the visible label (default: true)
     */
    public function __construct(
        public string $text,
        public string $label = 'Copy',
        public bool $iconOnly = true,
    ) {}

    /**
     * Labels the Alpine component swaps between (idle / copied / failed).
     *
     * @return array{label: string, copied: string, failed: string}
     */
    public function labels(): array
    {
        return [
            'label' => $this->label,
            'copied' => __('Copied'),
            'failed' => __('Copy failed'),
        ];
    }

    public function render()
    {
        return view('synapse::components.copy-button');
    }
}
