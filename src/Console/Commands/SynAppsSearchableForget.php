<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;
use VmEngine\Synapse\Models\Searchable;

use function Laravel\Prompts\confirm;

class SynAppsSearchableForget extends Command
{
    protected $signature = 'synapps:searchable-forget
        {prefix : Source prefix to delete; must start with "content:"}
        {--force : Delete without asking}';

    protected $description = 'Delete searchable content entries whose source starts with the given prefix';

    public function handle(): int
    {
        if (! synsearchable()->isEnabled()) {
            $this->components->info('Searchable is not set up. Run synapps:searchable-setup first.');

            return self::SUCCESS;
        }

        $prefix = $this->argument('prefix');

        if (! is_string($prefix) || ! str_starts_with($prefix, 'content:')) {
            $this->components->error('The prefix must start with "content:" (nav and module rows are managed by synapps:searchable-setup).');

            return self::FAILURE;
        }

        $count = Searchable::query()->sourcePrefix($prefix)->count();

        if ($count === 0) {
            $this->components->info("No searchable entries match [{$prefix}*].");

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! confirm("Delete {$count} searchable entries matching [{$prefix}*]?", default: false)) {
            $this->components->info('Cancelled.');

            return self::SUCCESS;
        }

        $deleted = synsearchable()->forgetSource($prefix);

        $this->components->success("Deleted {$deleted} searchable entries.");

        return self::SUCCESS;
    }
}
