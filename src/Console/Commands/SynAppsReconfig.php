<?php

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use stdClass;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class SynAppsReconfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'synapps:reconfig';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Interactive Configuration Updater for SynApps';

    public function handle(): int
    {
        $appCode = Str::snake(text(
            label: 'App code',
            hint: 'Use snake_case',
            required: true,
            default: config('synapps.apps.app_code', '')
        ));
        $appName = text(
            label: 'App name',
            required: true,
            default: config('synapps.apps.app_name', '')
        );
        $author = text(
            label: 'Author',
            required: true,
            default: config('synapps.apps.author', '')
        );
        $email = text(
            label: 'Author Email',
            required: true,
            default: config('synapps.apps.email', '')
        );
        $gitRepo = text('GIT Repository');
        $devDomain = text(
            label: 'Dev Domain URL',
            hint: 'Including proto',
            default: config('synapps.apps.urls.domain.dev', '')
        );
        $prodDomain = text(
            label: 'Prod Domain URL',
            hint: 'Including proto',
            default: config('synapps.apps.urls.domain.prod', '')
        );
        $frontendPrefix = text(
            label: 'Frontend Prefix',
            default: config('synapps.apps.urls.prefix', '')
        );
        $apiPrefix = text(
            label: 'API Prefix',
            default: config('synapps.apps.urls.api_prefix', '')
        );
        $appTitle = text(
            label: 'Application Title',
            default: config('synapps.apps.title', '')
        );
        $appDesc = text(
            label: 'Application Description',
            default: config('synapps.apps.description', '')
        );

        $multiLanguage = confirm(
            label: 'Enable Multi-Language Support?',
            default: config('synapps.apps.multi_language', false)
        );

        $enabledLanguages = ['en'];
        if ($multiLanguage) {
            $languagesInput = text(
                label: 'Enabled Languages',
                placeholder: 'en,id,es',
                default: implode(',', config('synapps.apps.enabled_languages', $enabledLanguages)),
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
            'csp' => config('synapps.apps.csp', [
                'script_src' => [],
                'style_src' => [],
                'font_src' => [],
                'img_src' => [],
                'connect_src' => [],
            ]),
            'logging' => config('synapps.apps.logging', [
                'sensitive_keys' => [
                    'password',
                    'password_confirmation',
                    'token',
                    'access_token',
                    'refresh_token',
                    'authorization',
                    'secret',
                    'api_key',
                    'card_number',
                    'cookie',
                    'set-cookie',
                    'x-xsrf-token',
                ],
                'max_body_size' => 65536,
            ]),
        ];

        file_put_contents(
            base_path('synapps'.DS.'config'.DS.'apps.json'),
            json_encode($appsJson, JSON_PRETTY_PRINT)
        );

        $this->components->success('Configuration updated.');

        $this->call('synapps:config');

        return 0;
    }
}
