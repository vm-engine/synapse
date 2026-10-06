<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;
use VmEngine\Synapse\Components\Concerns\SanitizesWireArguments;

class Steps extends Component
{
    use SanitizesWireArguments;

    /** @var list<string> */
    public array $steps;

    public ?string $goMethod;

    /**
     * @param  array<int, mixed>  $steps  Step labels in order (non-string/int entries are dropped)
     * @param  int  $current  1-based index of the active step (clamped)
     * @param  string|null  $goMethod  Host Livewire method called with the step number when a completed step is clicked
     */
    public function __construct(
        array $steps = [],
        public int $current = 1,
        ?string $goMethod = null,
    ) {
        $this->steps = array_values(array_map(
            fn (int|string $label): string => (string) $label,
            array_filter($steps, fn (mixed $label): bool => is_string($label) || is_int($label)),
        ));
        $this->current = max(1, min($current, max(1, count($this->steps))));
        $this->goMethod = $this->wireMethod($goMethod);
    }

    /**
     * @return list<array{number: int, label: string, state: 'complete'|'current'|'upcoming', clickable: bool}>
     */
    public function items(): array
    {
        $items = [];

        foreach ($this->steps as $index => $label) {
            $number = $index + 1;
            $state = match (true) {
                $number < $this->current => 'complete',
                $number === $this->current => 'current',
                default => 'upcoming',
            };

            $items[] = [
                'number' => $number,
                'label' => $label,
                'state' => $state,
                'clickable' => $this->goMethod !== null && $state === 'complete',
            ];
        }

        return $items;
    }

    public function render()
    {
        return view('synapse::components.steps');
    }
}
