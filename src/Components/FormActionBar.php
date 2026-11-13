<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

/** Sticky action bar for long dedicated-page forms: required-field hint, cancel link, save button. */
class FormActionBar extends Component
{
    public function __construct(
        public string $action = 'save',
        public ?string $cancelUrl = null,
        public ?string $saveLabel = null,
        public ?string $saveTarget = null,
        public string|bool|null $hint = null,
    ) {}

    public function render()
    {
        return view('synapse::components.form-action-bar');
    }
}
