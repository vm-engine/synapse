<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;
use VmEngine\Synapse\Notifications\NotificationSetup;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;

class SynotifSetup extends Command
{
    protected $signature = 'synotif:setup';

    protected $description = 'Enable/configure the in-app notification system';

    public function handle(): int
    {
        if (! NotificationSetup::authInstalled()) {
            $this->components->error(
                'In-app notifications require the vm-engine/synapps-auth package to be installed.'
            );

            return 1;
        }

        $enabled = confirm(
            label: 'Enable in-app notifications?',
            default: config('synapps.apps.notifications.enabled', false),
        );

        $logChannel = text(
            label: 'Log channel for skipped/disabled notifications (blank = silent)',
            default: config('synapps.apps.notifications.log_channel', ''),
        );
        $logChannel = $logChannel === '' ? null : $logChannel;

        if ($enabled && ! NotificationSetup::tableReady()) {
            NotificationSetup::ensureMigration($this);
            $this->call('migrate');
        }

        $appsJsonPath = base_path('synapps'.DS.'config'.DS.'apps.json');
        $appsJson = json_decode((string) file_get_contents($appsJsonPath), true);
        $appsJson['notifications'] = ['enabled' => $enabled, 'log_channel' => $logChannel];

        file_put_contents($appsJsonPath, json_encode($appsJson, JSON_PRETTY_PRINT));

        $this->call('synapps:config');

        $this->components->success('Notification configuration updated.');

        return 0;
    }
}
