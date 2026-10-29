<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\Components\AdvSelect;

function advSelectHtml(string $blade, array $data = []): string
{
    view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
    Blade::component(AdvSelect::class, 'synapse-adv-select');

    return Blade::render($blade, $data);
}

describe('AdvSelect Component', function () {
    it('renders x-data as raw JSON literals, not JSON.parse(), for CSP-safe Alpine', function () {
        // SynapseViewServiceProvider (which registers <x-synapse-*> tags)
        // only boots when SynapseUtils::isSynAppsAutoloaded() is true, which
        // this package's TestCase deliberately doesn't set up (see
        // BladeComponentsTest.php). Register just this tag directly.
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(AdvSelect::class, 'synapse-adv-select');

        // Livewire's csp_safe evaluator (forced on by
        // SynapseServiceProvider::overrideLivewireCsp()) has no access to the
        // `JSON` global, so @js()'s JSON.parse(...) wrapper silently breaks
        // x-data init. Regression test for that bug.
        $html = Blade::render(
            '<x-synapse-adv-select wire-model="filterRole" :live="true" :options="$options" />',
            ['options' => [['value' => 1, 'label' => 'Admin "Role"']]],
        );

        expect($html)->not->toContain('JSON.parse')
            ->and($html)->toContain('&quot;value&quot;:1')
            ->and($html)->toContain('&quot;label&quot;:&quot;Admin \\&quot;Role\\&quot;&quot;');
    });

    it('puts the single-select clear button beside the arrow, after the label', function () {
        $html = advSelectHtml('<x-synapse-adv-select wire-model="role" :options="[]" />');

        $label = strpos($html, 'adv-select-item-single');
        $actions = strpos($html, 'class="adv-select-actions"');
        $clear = strpos($html, 'class="adv-select-clear"');
        $arrow = strpos($html, 'adv-select-arrow');

        expect($actions)->not->toBeFalse()
            ->and($label)->toBeLessThan($actions)
            ->and($actions)->toBeLessThan($clear)
            ->and($clear)->toBeLessThan($arrow)
            ->and($html)->toContain('aria-label="Clear selection"');
    });

    it('renders a focusable combobox that forwards id and aria attributes', function () {
        $html = advSelectHtml('<x-synapse-adv-select wire-model="role" id="role-select" aria-labelledby="role-label" :options="[]" />');

        expect($html)->toContain('role="combobox"')
            ->toContain('aria-haspopup="listbox"')
            ->toContain(':tabindex="disabled ? -1 : 0"')
            ->toContain('id="role-select"')
            ->toContain('aria-labelledby="role-label"')
            ->toContain('@keydown.enter.prevent="toggleOpen"')
            ->toContain('role="option"');
    });

    it('can be instantiated with only the required wireModel', function () {
        $component = new AdvSelect(wireModel: 'selectedUser');

        expect($component)->toBeInstanceOf(AdvSelect::class);
    });

    it('stores the wireModel property', function () {
        $component = new AdvSelect(wireModel: 'role');

        expect($component->wireModel)->toBe('role');
    });

    it('defaults to single-select mode', function () {
        $component = new AdvSelect(wireModel: 'role');

        expect($component->multiple)->toBeFalse();
    });

    it('accepts multiple mode', function () {
        $component = new AdvSelect(wireModel: 'tags', multiple: true);

        expect($component->multiple)->toBeTrue();
    });

    it('defaults placeholder to "Select an option"', function () {
        $component = new AdvSelect(wireModel: 'role');

        expect($component->placeholder)->toBe('Select an option');
    });

    it('accepts a custom placeholder', function () {
        $component = new AdvSelect(wireModel: 'role', placeholder: 'Search users...');

        expect($component->placeholder)->toBe('Search users...');
    });

    it('defaults labelField and valueField to label/value', function () {
        $component = new AdvSelect(wireModel: 'role');

        expect($component->labelField)->toBe('label')
            ->and($component->valueField)->toBe('value');
    });

    it('defaults options to an empty array', function () {
        $component = new AdvSelect(wireModel: 'role');

        expect($component->options)->toBe([]);
    });

    it('accepts static options', function () {
        $options = [
            ['value' => 'admin', 'label' => 'Administrator'],
            ['value' => 'user', 'label' => 'User'],
        ];
        $component = new AdvSelect(wireModel: 'role', options: $options);

        expect($component->options)->toBe($options);
    });

    it('defaults preload to null', function () {
        $component = new AdvSelect(wireModel: 'role');

        expect($component->preload)->toBeNull();
    });

    it('defaults minSearchLength to 2', function () {
        $component = new AdvSelect(wireModel: 'role');

        expect($component->minSearchLength)->toBe(2);
    });

    it('defaults live, allowCreate to false and autoLoad to true', function () {
        $component = new AdvSelect(wireModel: 'role');

        expect($component->live)->toBeFalse()
            ->and($component->allowCreate)->toBeFalse()
            ->and($component->autoLoad)->toBeTrue();
    });

    it('has render method', function () {
        $component = new AdvSelect(wireModel: 'role');

        expect(method_exists($component, 'render'))->toBeTrue();
    });

    it('defaults disabled to false', function () {
        $component = new AdvSelect(wireModel: 'role');

        expect($component->disabled)->toBeFalse();
    });

    it('accepts disabled', function () {
        $component = new AdvSelect(wireModel: 'role', disabled: true);

        expect($component->disabled)->toBeTrue();
    });
});
