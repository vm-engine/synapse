<?php

declare(strict_types=1);

namespace Database\Seeders {
    use Illuminate\Database\Seeder;

    if (! class_exists(DatabaseSeeder::class)) {
        class DatabaseSeeder extends Seeder
        {
            public function run(): void {}
        }
    }
}

namespace Tests\Feature {
    use Database\Seeders\DatabaseSeeder;
    use Illuminate\Database\Seeder;
    use VmEngine\Synapse\SynapseServiceProvider;

    class ModuleSeederDependency
    {
        public static bool $used = false;
    }

    class ModuleSeederWithDependency extends Seeder
    {
        public function run(ModuleSeederDependency $dependency): void
        {
            $dependency::$used = true;
        }
    }

    it('injects run() dependencies into module seeders hooked onto DatabaseSeeder', function (): void {
        $seedFile = sys_get_temp_dir().'/synapse-seeders-'.uniqid().'.php';
        file_put_contents($seedFile, '<?php return ['.ModuleSeederWithDependency::class.'::class];');
        config(['synapse.seed_path.general' => [$seedFile]]);
        ModuleSeederDependency::$used = false;

        // Registered only in a configured SynApps install, so register it explicitly here.
        $provider = app()->getProvider(SynapseServiceProvider::class);
        (fn () => $this->bootDbOverride())->call($provider);

        try {
            app(DatabaseSeeder::class);
        } finally {
            unlink($seedFile);
        }

        expect(ModuleSeederDependency::$used)->toBeTrue();
    });
}
