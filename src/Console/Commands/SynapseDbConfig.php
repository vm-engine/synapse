<?php

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;

class SynapseDbConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'synapse:db-config
                            {key : Format: "group.key"}
                            {value?}
                            {--label= : String label for config}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage runtime configuration stored in database (DbConfig service)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        [$group, $key] = explode('.', $this->argument('key'), 2);

        $value = $this->argument('value');

        $label = $this->option('label');

        app('dbconf')->set($group, $key, $value, $label);

        $this->components->info('Config created successfully.');

        return 0;
    }
}
