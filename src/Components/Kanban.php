<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;
use VmEngine\Synapse\Components\Concerns\SanitizesWireArguments;

class Kanban extends Component
{
    use SanitizesWireArguments;

    public ?string $sortMethod;

    public string $group;

    /**
     * @param  string|null  $sortMethod  Host Livewire method called as sortMethod($itemId, $position, $destinationColumnId)
     * @param  string  $group  wire:sort group name shared by the board's columns
     */
    public function __construct(?string $sortMethod = null, string $group = 'kanban')
    {
        $this->sortMethod = $this->wireMethod($sortMethod);
        $this->group = $this->safeKey($group) ?? 'kanban';
    }

    public function render()
    {
        return view('synapse::components.kanban');
    }
}
