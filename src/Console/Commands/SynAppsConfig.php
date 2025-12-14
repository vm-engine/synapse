<?php

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;
use VmEngine\Synapse\Services\SynAppsConfigGenerator;
use VmEngine\Synapse\Services\SynapseConfigGenerator;

class SynAppsConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'synapps:config';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Regenerate configuration files from JSON sources (config/synapps.php, config/synapse.php)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        SynAppsConfigGenerator::generate();
        SynapseConfigGenerator::generate();

        $this->components->info('SynApps & Synapse configuration files regenerated successfully.');

        return 0;
    }
}
