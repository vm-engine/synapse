<?php

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;

class SynapseDbLang extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'synapse:db-lang
                            {key}
                            {value?}
                            {--label= : String label for config}
                            {--language=en : Language code to use}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage translations stored in database (DbLang service)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $key = $this->argument('key');

        $value = $this->argument('value');

        $label = $this->option('label');

        $langCode = $this->option('language');

        app('dblang')->set($key, $value, $label, $langCode);

        $this->components->info('Language created successfully.');

        return 0;
    }
}
