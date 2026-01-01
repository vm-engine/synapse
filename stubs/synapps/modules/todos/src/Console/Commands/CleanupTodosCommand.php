<?php

declare(strict_types=1);

namespace SynApps\Modules\Todos\Console\Commands;

use Illuminate\Console\Command;

class CleanupTodosCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:{{ command }}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        return Command::SUCCESS;
    }
}
