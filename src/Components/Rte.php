<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use VmEngine\Synapse\Rte\RteSetup;

/**
 * Jodit rich-text editor bound to a Livewire property (deferred, like wire:model).
 * Optional: when `rte.enabled` is off (see RteSetup) it shows an error and a plain textarea
 * bound to the same property, so the form keeps working.
 * Always clean the submitted HTML server-side (VmEngine\Synapse\Rte\RichText::clean).
 */
class Rte extends Component
{
    /**
     * @param  string  $field  the Livewire property, e.g. "body" or "form.body"
     * @param  list<string>|null  $buttons  Jodit toolbar button names; the default set when null
     * @param  string|null  $extension  name registered with window.synapseRte.extend() to add buttons
     * @param  array<string, mixed>  $extensionConfig  handed to that extension
     */
    public function __construct(
        public string $field,
        public ?string $value = '',
        public int $height = 360,
        public ?array $buttons = null,
        public ?string $extension = null,
        public array $extensionConfig = [],
    ) {}

    public function enabled(): bool
    {
        return RteSetup::enabled();
    }

    public function disabledMessage(): string
    {
        return RteSetup::DISABLED_MESSAGE;
    }

    public function inputId(): string
    {
        return 'rte-'.str_replace(['.', '[', ']'], '-', $this->field);
    }

    /**
     * Config handed to the synapseRte Alpine component (synapse-rte.js).
     *
     * @return array<string, mixed>
     */
    public function config(): array
    {
        $config = ['field' => $this->field, 'height' => $this->height];

        if ($this->buttons !== null) {
            $config['buttons'] = $this->buttons;
        }

        if ($this->extension !== null) {
            $config['extension'] = $this->extension;
            $config['extensionConfig'] = $this->extensionConfig;
        }

        return $config;
    }

    public function render(): View
    {
        return view('synapse::components.rte');
    }
}
