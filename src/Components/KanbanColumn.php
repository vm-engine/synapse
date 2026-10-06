<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;
use VmEngine\Synapse\Components\Concerns\SanitizesWireArguments;

class KanbanColumn extends Component
{
    use SanitizesWireArguments;

    /** @var array<int, string> */
    private const COLORS = ['gray', 'brand', 'success', 'warning', 'danger', 'info'];

    public ?string $id;

    public function __construct(
        int|string $id,
        public string $label = '',
        public string $color = 'gray',
        public ?int $count = null,
        public string $emptyText = 'No items',
    ) {
        $this->id = $this->safeKey($id);

        if (! in_array($this->color, self::COLORS, true)) {
            $this->color = 'gray';
        }
    }

    public function render()
    {
        return view('synapse::components.kanban-column');
    }
}
