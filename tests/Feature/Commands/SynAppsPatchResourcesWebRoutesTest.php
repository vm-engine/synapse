<?php

use Illuminate\Support\Facades\File;

/**
 * Tests for SynAppsPatchResources --comment-default (patchWebRoutes) welcome-route detection.
 *
 * Files are created in the Testbench Laravel root (base_path()) and cleaned up after each test.
 */
afterEach(function () {
    File::delete(base_path('routes/web.php'));
});

it('comments out the closure-based default route', function () {
    File::ensureDirectoryExists(base_path('routes'));
    File::put(base_path('routes/web.php'), <<<'PHP'
        <?php

        use Illuminate\Support\Facades\Route;

        Route::get('/', function () {
            return view('welcome');
        });
        PHP);

    $this->artisan('synapps:patch-resources', ['--comment-default' => true]);

    expect(File::get(base_path('routes/web.php')))
        ->toContain("// Route::get('/', function () {");
});

it('comments out the Route::view based default route (newer Laravel skeleton)', function () {
    File::ensureDirectoryExists(base_path('routes'));
    File::put(base_path('routes/web.php'), <<<'PHP'
        <?php

        use Illuminate\Support\Facades\Route;

        Route::view('/', 'welcome')->name('home');
        PHP);

    $this->artisan('synapps:patch-resources', ['--comment-default' => true]);

    expect(File::get(base_path('routes/web.php')))
        ->toContain("// Route::view('/', 'welcome')->name('home');");
});

it('leaves the default route alone when other routes already exist in the file', function () {
    File::ensureDirectoryExists(base_path('routes'));
    File::put(base_path('routes/web.php'), <<<'PHP'
        <?php

        use Illuminate\Support\Facades\Route;

        Route::view('/', 'welcome')->name('home');
        Route::get('/status', fn () => 'ok');
        PHP);

    $this->artisan('synapps:patch-resources', ['--comment-default' => true]);

    expect(File::get(base_path('routes/web.php')))
        ->toContain("Route::view('/', 'welcome')->name('home');")
        ->not->toContain("// Route::view('/', 'welcome')->name('home');");
});
