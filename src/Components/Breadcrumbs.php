<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;
use VmEngine\Synapse\Services\Helper\Breadcrumbs as Trail;

/** Page title (from Alpine's `pageName`) plus the breadcrumb trail of a backend page. */
class Breadcrumbs extends Component
{
    /** @var list<array{label: string, url: string, icon?: string|null, last: bool}> */
    public array $crumbs;

    public function __construct(public ?Trail $breadcrumbs = null)
    {
        $items = array_values($breadcrumbs?->items() ?? []);
        $lastIndex = count($items) - 1;

        $this->crumbs = array_map(
            fn (array $item, int $index): array => [...$item, 'last' => $index === $lastIndex],
            $items,
            array_keys($items),
        );
    }

    /** Dashboard URL; synapps-auth is optional, so fall back to the site root without it. */
    public function homeUrl(): string
    {
        return function_exists('backend_route') ? backend_route('home.dashboard') : url('/');
    }

    public function render()
    {
        return view('synapse::components.breadcrumbs');
    }
}
