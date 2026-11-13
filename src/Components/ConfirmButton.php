<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;
use VmEngine\Synapse\Components\Concerns\SanitizesWireArguments;

/**
 * Button that opens <x-synapse-confirm-dialog> for a Livewire method. Pass `token`
 * (a model's delete_token) for deletes and `param` for any other action — never a raw id for a delete.
 */
class ConfirmButton extends Component
{
    use SanitizesWireArguments;

    public string $safeMethod;

    public function __construct(
        public string $method,
        public string $label,
        public string $title,
        public string $message,
        public ?string $token = null,
        public int|string|null $param = null,
        public string $icon = 'ph ph-trash',
        public string $color = 'danger',
        public ?string $component = null,
        public bool $labelled = false,
        public ?string $confirmText = null,
    ) {
        $this->safeMethod = $this->wireMethod($method) ?? '';
    }

    public function render()
    {
        return view('synapse::components.confirm-button');
    }
}
