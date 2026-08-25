<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class PasswordGenerator extends Component
{
    public int $length;

    /** @var string|array<int|string, mixed>|null */
    public string|array|null $model;

    /** @param  string|array<int|string, mixed>|null  $model */
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
