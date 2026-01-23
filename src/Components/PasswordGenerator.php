<?php

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class PasswordGenerator extends Component
{
    public int $length;

    public string|array|null $model;

    public function __construct(
        string|array|null $model = null,
        int $length = 12,
    ) {
        $this->model = $model;
        $this->length = max(8, $length); // Ensure minimum length of 8
    }

    public function render()
    {
        return view('synapse::components.password-generator');
    }
}
