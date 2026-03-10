<?php

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use stdClass;
use VmEngine\Synapse\Services\SynapseUtils;

use function Laravel\Prompts\confirm;
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
    protected $description = 'Interactive setup wizard to initialize Synapse modular architecture';

    public function handle(): int
    {
        $key = config('app.key');
        if (! $key) {
            $this->components->error('Pleasee run `key:generate` before continue');

            return 1;
        }
        $appCode = Str::snake(text(
            label: 'App code',
            hint: 'Use snake_case',
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

        $multiLanguage = confirm(
            label: 'Enable Multi-Language Support?',
            default: false,
        );

        $enabledLanguages = ['en'];
        if ($multiLanguage) {
            $languagesInput = text(
                label: 'Enabled Languages',
                placeholder: 'en,id,es',
                default: 'en',
                hint: 'Comma-separated language codes (e.g., en,id,es)',
            );
            $enabledLanguages = array_map('trim', explode(',', $languagesInput));
        }

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
            'multi_language' => $multiLanguage,
            'enabled_languages' => $enabledLanguages,
            'dependencies' => [
                'npm' => [
                    'main' => [
                        'jquery' => '^3.7',
                        '@alpinejs/mask' => '^3.15.0',
                        '@alpinejs/intersect' => '^3.15.0',
                        '@alpinejs/resize' => '^3.15.0',
                        '@alpinejs/persist' => '^3.15.0',
                        '@alpinejs/focus' => '^3.15.0',
                        '@alpinejs/collapse' => '^3.15.0',
                        '@alpinejs/anchor' => '^3.15.0',
                        '@alpinejs/morph' => '^3.15.0',
                        '@alpinejs/sort' => '^3.15.0',
                        '@fortawesome/fontawesome-free' => '^7.1.0',
                        'flatpickr' => '^4.6.13',
                        'glob' => '^13.0.0',
                        'vite-plugin-static-copy' => '^3.1.4',
                    ],
                    'dev' => [
                        'vite-plugin-static-copy' => '^3.1.4',
                        'glob' => '^11.0.0',
                    ],
                ],
                'composer' => [
                    'main' => new stdClass,
                    'dev' => new stdClass,
                ],
            ],
            'themes' => [
                'active' => 'default',
                'fallback' => 'default',
                'css' => [
                    'all' => [],
                    'app' => [],
                    'guest' => [],
                    'frontend' => [],
                ],
                'js' => [
                    'all' => [],
                    'app' => [],
                    'guest' => [],
                    'frontend' => [],
                ],
                'headers' => [
                    'all' => [],
                    'app' => [],
                    'guest' => [],
                    'frontend' => [],
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

        $systemFile = synapps_path('config/system.json');
        $jsonSystem = json_decode(
            file_get_contents($systemFile),
            true
        );
        $jsonSystem['laravel_key'] = $key;
        file_put_contents(
            $systemFile,
            json_encode($jsonSystem, JSON_PRETTY_PRINT)
        );

        // Setup default theme
        $this->setupThemes();

        $this->call('vendor:publish', [
            '--tag' => 'synapse-config',
            '--force' => true,
        ]);
        $this->call('synapps:config');
        $this->call('synapps:link-errors');
        $this->call('synapps:patch-resources');
        $this->call('synapps:patch-tests');
        $this->call('synapps:discover');
        $this->call('migrate');

        $this->components->success('Setup complete.');
        $this->components->warn("Don't forget to init Git Repository in synapps folder or your project root.");

        return 0;
    }

    /**
     * Setup default theme
     */
    protected function setupThemes(): void
    {
        $themesPath = base_path('synapps/resources/themes');

        if (! is_dir($themesPath)) {
            mkdir($themesPath, 0755, true);
        }

        // Copy default theme from package stubs
        $defaultThemeStub = realpath(__DIR__.DS.'..'.DS.'..'.DS.'..'.DS.'stubs'.DS.'themes'.DS.'default');
        $defaultThemeDest = $themesPath.'/default';

        if (is_dir($defaultThemeStub) && ! is_dir($defaultThemeDest)) {
            if (SynapseUtils::copyDirectory($defaultThemeStub, $defaultThemeDest)) {
                $this->components->info('Default theme installed.');
            }
        }
    }
}
