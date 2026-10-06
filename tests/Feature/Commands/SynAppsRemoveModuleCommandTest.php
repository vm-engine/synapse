<?php

declare(strict_types=1);

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use VmEngine\Synapse\Console\Commands\SynAppsDiscover;

beforeEach(function () {
    $this->modulePath = base_path('synapps/modules/demo-removable');
    mkdir($this->modulePath, 0755, true);
    file_put_contents($this->modulePath.'/module.json', json_encode(['id' => 'demo-removable']));

    // Replace the real synapps:discover with a no-op fake so tests don't
    // shell out to `composer update` / `npm i`.
    $this->app->bind(SynAppsDiscover::class, fn () => new class extends Command
    {
        protected $signature = 'synapps:discover';

        protected $description = 'Fake synapps:discover for tests';

        public function handle(): int
        {
            $this->components->info('fake discover ran');

            return self::SUCCESS;
        }
    });
});

afterEach(function () {
    (new Filesystem)->deleteDirectory($this->modulePath);
    (new Filesystem)->deleteDirectory(base_path('.trash'));

    if (isset($this->gitignoreBackup)) {
        file_put_contents(base_path('.gitignore'), $this->gitignoreBackup);
    }
});

test('synapps:remove-module fails when the module does not exist', function () {
    $this->artisan('synapps:remove-module', ['moduleName' => 'does-not-exist'])
        ->assertFailed();
});

test('synapps:remove-module cancels when not confirmed', function () {
    $this->artisan('synapps:remove-module', ['moduleName' => 'demo-removable'])
        ->expectsConfirmation('Move module "demo-removable" to .trash? It can be restored manually afterwards.', 'no')
        ->assertSuccessful();

    expect($this->modulePath)->toBeDirectory();
});

test('synapps:remove-module moves the module to .trash and runs discover', function () {
    $this->artisan('synapps:remove-module', ['moduleName' => 'demo-removable'])
        ->expectsConfirmation('Move module "demo-removable" to .trash? It can be restored manually afterwards.', 'yes')
        ->expectsOutputToContain('fake discover ran')
        ->assertSuccessful();

    expect($this->modulePath)->not->toBeDirectory();

    $trashed = glob(base_path('.trash/demo-removable-*'));
    expect($trashed)->toHaveCount(1)
        ->and($trashed[0].'/module.json')->toBeFile();
});

test('synapps:remove-module --permanent deletes the module without trashing it', function () {
    $this->artisan('synapps:remove-module', ['moduleName' => 'demo-removable', '--permanent' => true])
        ->expectsConfirmation('Permanently delete module "demo-removable"? This cannot be undone.', 'yes')
        ->assertSuccessful();

    expect($this->modulePath)->not->toBeDirectory()
        ->and(glob(base_path('.trash/demo-removable-*')))->toBe([]);
});

test('synapps:remove-module adds /.trash to .gitignore when missing', function () {
    $this->gitignoreBackup = file_get_contents(base_path('.gitignore'));
    file_put_contents(base_path('.gitignore'), "/vendor\n.env\n");

    $this->artisan('synapps:remove-module', ['moduleName' => 'demo-removable'])
        ->expectsConfirmation('Move module "demo-removable" to .trash? It can be restored manually afterwards.', 'yes')
        ->assertSuccessful();

    expect(file_get_contents(base_path('.gitignore')))->toContain("/.trash\n");
});

test('synapps:remove-module does not duplicate an existing /.trash entry in .gitignore', function () {
    $this->gitignoreBackup = file_get_contents(base_path('.gitignore'));
    file_put_contents(base_path('.gitignore'), "/vendor\n/.trash\n.env\n");

    $this->artisan('synapps:remove-module', ['moduleName' => 'demo-removable'])
        ->expectsConfirmation('Move module "demo-removable" to .trash? It can be restored manually afterwards.', 'yes')
        ->assertSuccessful();

    $count = substr_count(file_get_contents(base_path('.gitignore')), '/.trash');
    expect($count)->toBe(1);
});
