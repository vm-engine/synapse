<?php

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;
use VmEngine\Synapse\Services\SynapseUtils;

class SynAppsPublishSettings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'synapps:publish-settings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Publish Settings module to synapps/modules (requires vm-engine/synapps-auth)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (! class_exists('\\VmEngine\\SynAuth\\SynAuthServiceProvider')) {
            $this->components->error('SynAuth is required for this module');

            return 1;
        }

        SynapseUtils::copyDirectory(
            realpath(__DIR__.DS.'..'.DS.'..'.DS.'..'.DS.'stubs'.DS.'modules'.DS.'settings'),
            synapps_path('modules'.DS.'settings')
        );
        $this->components->info('Settings module has been published');

        return 0;
    }
}
