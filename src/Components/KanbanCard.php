<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;
use VmEngine\Synapse\Components\Concerns\SanitizesWireArguments;

class KanbanCard extends Component
{
    use SanitizesWireArguments;

    public ?string $id;

    public function __construct(int|string $id)
    {
        $this->id = $this->safeKey($id);
    }

    public function render()
    {
        return view('synapse::components.kanban-card');
    }
}
