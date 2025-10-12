<?php

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use stdClass;
use VmEngine\Synapse\Services\SynapseUtils;

use function Laravel\Prompts\text;

class SynAppsSetup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'synapps:setup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup SynApps folder';

    public function handle()
    {
        $appCode = Str::snake(text(
            label: 'App code',
            hint: 'Use snake-case',
            required: true
        ));
        $appName = text(
            label: 'App name',
            required: true
        );
        $author = text(
            label: 'Author',
            required: true,
        );
        $email = text(
            label: 'Author Email',
            required: true,
        );
        $gitRepo = text('GIT Repository');
        $devDomain = text(
            label: 'Dev Domain URL',
            hint: 'Including proto',
        );
        $prodDomain = text(
            label: 'Prod Domain URL',
            hint: 'Including proto',
        );
        $frontendPrefix = text(
            label: 'Frontend Prefix',
            default: '/',
        );
        $apiPrefix = text(
            label: 'API Prefix',
            default: '/api',
        );
        $appTitle = text(
            label: 'Application Title',
            default: $appName,
        );
        $appDesc = text(
            label: 'Application Description',
        );
        $appsJson = [
            'app_code' => $appCode,
            'app_name' => $appName,
            'author' => $author,
            'email' => $email,
            'git_repo' => $gitRepo,
            'base_version' => '',
            'urls' => [
                'domain' => [
                    'dev' => $devDomain,
                    'prod' => $prodDomain,
                ],
                'prefix' => $frontendPrefix,
                'api_prefix' => $apiPrefix,
            ],
            'title' => $appTitle,
            'description' => $appDesc,
            'dependencies' => [
                'npm' => [
                    'main' => [
                        'jquery' => '^3.7',
                        '@alpinejs/mask' => '^3.15.0',
                        '@fortawesome/fontawesome-free' => '^7.1.0',
                        'flatpickr' => '^4.6.13',
                    ],
                    'dev' => new stdClass,
                ],
                'composer' => [
                    'main' => new stdClass,
                    'dev' => new stdClass,
                ],
            ],
        ];

        if (! file_exists(base_path('synapps'))) {
            mkdir(base_path('synapps'), 0755);
        }
        if (! SynapseUtils::copyDirectory(
            realpath(__DIR__.DS.'..'.DS.'..'.DS.'..'.DS.'stubs'.DS.'synapps'),
            base_path('synapps')
        )) {
            $this->components->error('Failed to copy synapps folder');

            return 1;
        }

        file_put_contents(
            base_path('synapps'.DS.'config'.DS.'apps.json'),
            json_encode($appsJson, JSON_PRETTY_PRINT)
        );

        $this->components->success('Setup complete.');
        $this->components->warn('Run `vendor:publish --tag="synapse-config"` before using another command.');
        $this->components->info('Run `synapps:discover` and `config:cache` artisan command before migrating.');
        $this->components->info('Run `synapps:patch-resources` before compiling assets');
        $this->components->info('Remove Laravel default Route in `routes/web.php` to start using Home module as default Route');
        $this->components->info("Don't forget to init Git Repository in synapps folder");
    }
}
