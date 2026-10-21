<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\Components\Rte;
use VmEngine\Synapse\Rte\RteSetup;

beforeEach(function () {
    // The package TestCase does not boot SynapseViewServiceProvider; register just this tag.
    view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
    Blade::component(Rte::class, 'synapse-rte');
});

it('renders the editor host bound to the field when enabled', function () {
    config(['synapps.apps.rte.enabled' => true]);

    $html = Blade::render('<x-synapse-rte field="form.body" :value="$value" :height="500" />', ['value' => '<p>Hi & bye</p>']);

    expect($html)->toContain('wire:ignore')
        ->toContain('x-data="synapseRte(')
        ->toContain('&quot;field&quot;:&quot;form.body&quot;')
        ->toContain('&quot;height&quot;:500')
        ->toContain('id="rte-form-body"')
        ->toContain('&lt;p&gt;Hi &amp; bye&lt;/p&gt;')
        ->not->toContain('JSON.parse')
        ->not->toContain('extension');
});

it('passes custom buttons and an extension to the script', function () {
    config(['synapps.apps.rte.enabled' => true]);

    $html = Blade::render(
        '<x-synapse-rte field="body" :buttons="$buttons" extension="cms-fm" :extension-config="$config" />',
        ['buttons' => ['bold', '|', 'link'], 'config' => ['pickerKey' => 'k1']],
    );

    expect($html)->toContain('&quot;buttons&quot;:[&quot;bold&quot;,&quot;|&quot;,&quot;link&quot;]')
        ->toContain('&quot;extension&quot;:&quot;cms-fm&quot;')
        ->toContain('&quot;extensionConfig&quot;:{&quot;pickerKey&quot;:&quot;k1&quot;}');
});

it('shows the disabled message and a plain bound textarea when the feature is off', function () {
    $html = Blade::render('<x-synapse-rte field="form.body" value="<p>kept</p>" />');

    expect($html)->toContain(e(RteSetup::DISABLED_MESSAGE))
        ->toContain('role="alert"')
        ->toContain('<textarea')
        ->toContain('wire:model="form.body"')
        ->toContain('id="rte-form-body"')
        ->toContain('&lt;p&gt;kept&lt;/p&gt;')
        ->not->toContain('synapseRte')
        ->not->toContain('wire:ignore');
});

it('ships a script that registers synapseRte and the extension hook', function () {
    $stub = (string) file_get_contents(__DIR__.'/../../../stubs/synapps/resources/js/synapse-rte.js');

    expect($stub)->toContain("Alpine.data('synapseRte'")
        ->toContain('window.synapseRte')
        ->toContain("import('jodit')");
});

it('registers synapseRte even when Alpine is already running (after a wire:navigate)', function () {
    $stub = (string) file_get_contents(__DIR__.'/../../../stubs/synapps/resources/js/synapse-rte.js');

    // alpine:init fires once per full page load; a navigate from another layout loads this entry
    // with Alpine already started, so an alpine:init-only registration would never run.
    expect($stub)->toMatch('/if \\(window\\.Alpine\\) \\{\\s*registerSynapseRte\\(\\);/')
        ->toContain("document.addEventListener('alpine:init', registerSynapseRte)");
});
