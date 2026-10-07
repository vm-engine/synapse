<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use stdClass;
use VmEngine\Synapse\Http\Middlewares\IpFilter;
use VmEngine\Synapse\Notifications\NotificationSetup;
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

        $notificationsEnabled = false;
        $notificationsLogChannel = null;

        if (NotificationSetup::authInstalled()) {
            $notificationsEnabled = confirm(
                label: 'Enable in-app notifications?',
                default: false,
            );

            if ($notificationsEnabled) {
                $notificationsLogChannelInput = text(
                    label: 'Log channel for skipped/disabled notifications (blank = silent)',
                );
                $notificationsLogChannel = $notificationsLogChannelInput === '' ? null : $notificationsLogChannelInput;
            }
        } else {
            $this->components->info('Notifications skipped — install vm-engine/synapps-auth to enable.');
        }

        $ipWhitelist = IpFilter::parseList(text(
            label: 'Whitelisted IPs',
            hint: 'Comma-separated IPs or CIDR ranges. Blank = allow all',
            validate: fn (string $value) => $this->invalidIpMessage($value),
        ));
        $ipBlacklist = IpFilter::parseList(text(
            label: 'Blacklisted IPs',
            hint: 'Comma-separated IPs or CIDR ranges. Blank = block none',
            validate: fn (string $value) => $this->invalidIpMessage($value),
        ));

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
            'notifications' => [
                'enabled' => $notificationsEnabled,
                'log_channel' => $notificationsLogChannel,
            ],
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
                        '@phosphor-icons/web' => '^2.1.2',
                        'flatpickr' => '^4.6.13',
                        'vite-plugin-static-copy' => '^3.1.4',
                    ],
                    'dev' => [
                        'vite-plugin-static-copy' => '^3.1.4',
                        'glob' => '^13.0.0',
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
            'csp' => [
                'script_src' => [],
                'style_src' => [],
                'font_src' => [],
                'img_src' => [],
                'connect_src' => [],
            ],
            'security' => [
                'ssrf_allow_loopback' => false,
                'ip_whitelist' => $ipWhitelist,
                'ip_blacklist' => $ipBlacklist,
            ],
            'logging' => [
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
            ],
        ];

        if (! file_exists(base_path('synapps'))) {
            mkdir(base_path('synapps'), 0755);
        }

        $stubsPath = realpath(__DIR__.DS.'..'.DS.'..'.DS.'..'.DS.'stubs'.DS.'synapps');

        if ($stubsPath === false || ! SynapseUtils::copyDirectory($stubsPath, base_path('synapps'))) {
            $this->components->error('Failed to copy synapps folder');

            return 1;
        }

        file_put_contents(
            base_path('synapps'.DS.'config'.DS.'apps.json'),
            json_encode($appsJson, JSON_PRETTY_PRINT)
        );

        // Setup default theme
        $this->setupThemes();

        $this->call('vendor:publish', [
            '--tag' => 'synapse-config',
            '--force' => true,
        ]);
        $this->call('synapps:config');
        $this->call('synapps:link-errors');
        $this->call('synapps:patch');
        $this->call('synapps:discover');

        if ($notificationsEnabled) {
            NotificationSetup::ensureMigration($this);
        }

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

        if ($defaultThemeStub !== false && is_dir($defaultThemeStub) && ! is_dir($defaultThemeDest)) {
            if (SynapseUtils::copyDirectory($defaultThemeStub, $defaultThemeDest)) {
                $this->components->info('Default theme installed.');
            }
        }
    }

    /**
     * Prompt validation: error message naming invalid IP/CIDR entries, or null when all are valid.
     */
    protected function invalidIpMessage(string $value): ?string
    {
        $invalid = array_filter(IpFilter::parseList($value), fn (string $entry) => ! IpFilter::isValidEntry($entry));

        return $invalid === [] ? null : 'Invalid IP/CIDR: '.implode(', ', $invalid);
    }
}
