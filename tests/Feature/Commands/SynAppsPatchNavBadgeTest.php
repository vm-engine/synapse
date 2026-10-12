<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/** synapps:patch layouts adds the nav-badge line to a host's copies of the nav item views. */
beforeEach(function () {
    // The other layouts steps write here too (see SynAppsPatchResourcesSplitTest).
    foreach ([resource_path('css'), resource_path('js'), base_path('synapps/resources/css'), base_path('synapps/resources/js'),
        base_path('synapps/resources/themes/default/css'), base_path('synapps/resources/themes/default/js')] as $dir) {
        File::makeDirectory($dir, 0755, true, true);
    }
    $this->navDir = base_path('synapps/resources/views/components/layouts/nav');
    File::makeDirectory($this->navDir, 0755, true, true);
    $this->stubDir = __DIR__.'/../../../stubs/synapps/resources/views/components/layouts/nav';
    $this->line = '<x-synapse-nav-badge :item="$item" />';
    // What an existing host has: the stub without the badge line.
    $this->legacy = fn (string $file): string => preg_replace('/\n\s*'.preg_quote($this->line, '/').'\n/', "\n", (string) file_get_contents($this->stubDir.'/'.$file));
    config(['synapps.apps.themes.active' => 'default']);
});

afterEach(function () {
    File::deleteDirectory($this->navDir);
    File::delete([
        resource_path('css/base.css'), resource_path('css/app.css'), resource_path('css/frontend.css'), resource_path('css/guest.css'), resource_path('css/app.css.bak'),
        resource_path('js/base.js'), resource_path('js/app.js'), resource_path('js/frontend.js'), resource_path('js/guest.js'), resource_path('js/app.js.bak'),
        base_path('synapps/resources/js/synapse-components.js'), base_path('synapps/resources/js/synapps.js'),
        base_path('synapps/resources/css/synapps-base.css'), base_path('synapps/resources/css/synapps-app.css'),
        base_path('synapps/resources/css/synapps-frontend.css'), base_path('synapps/resources/css/synapps-guest.css'),
        base_path('synapps/resources/css/tailwind-ref.css'),
        base_path('synapps/resources/js/synapps-base.js'), base_path('synapps/resources/js/synapps-app.js'),
        base_path('synapps/resources/js/synapps-frontend.js'), base_path('synapps/resources/js/synapps-guest.js'),
        base_path('vite.config.js'), base_path('vite.config.js.bak'),
    ]);
});

it('inserts the badge line into both views and reproduces the stubs exactly', function () {
    foreach (['menu-item.blade.php', 'submenu-item.blade.php'] as $file) {
        File::put($this->navDir.'/'.$file, ($this->legacy)($file));
        expect(File::get($this->navDir.'/'.$file))->not->toContain($this->line);
    }

    $this->artisan('synapps:patch', ['object' => ['layouts']])->assertSuccessful();

    foreach (['menu-item.blade.php', 'submenu-item.blade.php'] as $file) {
        expect(File::get($this->navDir.'/'.$file))->toBe((string) file_get_contents($this->stubDir.'/'.$file));
    }
});

it('is safe to run again', function () {
    File::put($this->navDir.'/menu-item.blade.php', ($this->legacy)('menu-item.blade.php'));
    $this->artisan('synapps:patch', ['object' => ['layouts']]);
    $once = File::get($this->navDir.'/menu-item.blade.php');
    $this->artisan('synapps:patch', ['object' => ['layouts']]);

    expect(File::get($this->navDir.'/menu-item.blade.php'))->toBe($once)
        ->and(substr_count($once, $this->line))->toBe(1);
});

it('leaves a customised view alone and says which line to add', function () {
    File::put($this->navDir.'/menu-item.blade.php', "<li><a>{{ \$item['label'] }}</a></li>\n");

    $this->artisan('synapps:patch', ['object' => ['layouts']])
        ->expectsOutputToContain('menu-item.blade.php')
        ->assertSuccessful();

    expect(File::get($this->navDir.'/menu-item.blade.php'))->toBe("<li><a>{{ \$item['label'] }}</a></li>\n");
});

it('does nothing when the host has no nav views', function () {
    $this->artisan('synapps:patch', ['object' => ['layouts']])->assertSuccessful();

    expect(File::exists($this->navDir.'/menu-item.blade.php'))->toBeFalse();
});
