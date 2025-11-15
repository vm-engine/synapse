<?php

declare(strict_types=1);

namespace SynApps\Modules\Todos\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('synapps::components.layouts.layout')]
#[Title('Todos')]
class TodoIndex extends Component
{
    public function render()
    {
        return view('todos::livewire.todo-index');
    }
}
