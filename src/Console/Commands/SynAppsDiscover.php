<?php

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * @SuppressWarnings(PHPMD.StaticAccess)
 * @SuppressWarnings(PHPMD.ElseStatement)
 */
class SynAppsDiscover extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'synapps:discover';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Discover Modules in synapps folder';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);
        $moduleConfigPaths = glob(synapps_path('modules/*/module.json'));

        $namespaces = [];
        foreach ($moduleConfigPaths as $moduleConfigPath) {
            $moduleConfig = json_decode(file_get_contents($moduleConfigPath), true);
            $modulePath = ltrim(str_replace(
                base_path(),
                "",
                dirname($moduleConfigPath)
            ));
            $moduleNamespace = "SynApps\\Modules\\" . $moduleConfig['id_case']['pascal'] . "\\";
            $namespaces[$moduleNamespace] = ltrim($modulePath, DS) . DS . "src" . DS;
            $this->info("Discovered Module: {$moduleConfig['name']} at {$modulePath}");
        }

        $this->info('Discovered ' . count($namespaces) . ' Modules');
        $composerJson['autoload']['psr-4'] = array_merge(
            $composerJson['autoload']['psr-4'], 
            $namespaces
        );

        $this->info('Updating composer.json');
        file_put_contents(
            base_path('composer.json'),
            json_encode($composerJson, JSON_PRETTY_PRINT)
        );

        $this->info('Running composer dump-autoload');
        exec('composer dump-autoload');

        $this->info('Caching Config');
        Artisan::call('config:cache');
    }
}
