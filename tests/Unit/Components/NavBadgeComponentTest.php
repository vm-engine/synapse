<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\Components\Badge;
use VmEngine\Synapse\Components\NavBadge as NavBadgeComponent;
use VmEngine\Synapse\Nav\NavBadge;

class ComponentTestBadge extends NavBadge
{
    public static int $value = 7;

    public function count(): ?int
    {
        return self::$value;
    }

    public function ttl(): ?int
    {
        return 0;
    }
}

beforeEach(function () {
    // <x-synapse-*> tags are only auto-registered in a synapps host (see BladeComponentsTest).
    view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
    Blade::component(Badge::class, 'synapse-badge');
    Blade::component(NavBadgeComponent::class, 'synapse-nav-badge');
    $this->render = fn (array $item): string => trim(Blade::render('<x-synapse-nav-badge :item="$item" />', ['item' => $item]));
});

it('renders the count as a small badge that hides with the collapsed sidebar', function () {
    ComponentTestBadge::$value = 7;
    $html = ($this->render)(['id' => 'x', 'name' => 'X', 'badge' => ComponentTestBadge::class]);

    expect($html)->toContain('syn-badge-sm')->toMatch('/>\s*7\s*</')
        ->toContain(":class=\"sidebarToggle ? 'lg:hidden' : ''\"")
        ->toContain('syn-nav-badge');
});

it('renders nothing at all without a count', function (array $item) {
    ComponentTestBadge::$value = 0;

    expect(($this->render)($item))->toBe('');
})->with([
    'zero' => [['id' => 'x', 'name' => 'X', 'badge' => ComponentTestBadge::class]],
    'no badge key' => [['id' => 'x', 'name' => 'X']],
]);

it('is called from both nav stubs, right after the item name', function (string $stub) {
    $view = (string) file_get_contents(__DIR__.'/../../../stubs/synapps/resources/views/components/layouts/nav/'.$stub);

    expect(substr_count($view, '<x-synapse-nav-badge :item="$item" />'))->toBe(1)
        ->and(strpos($view, '<x-synapse-nav-badge'))->toBeGreaterThan(strrpos($view, "synav()->translate(\$item['name'])"))
        ->and(strpos($view, '<x-synapse-nav-badge'))->toBeLessThan(strpos($view, '<x-synapps::layouts.nav.arrow'));
})->with(['menu-item.blade.php', 'submenu-item.blade.php']);
