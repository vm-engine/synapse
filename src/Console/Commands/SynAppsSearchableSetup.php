<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;
use VmEngine\Synapse\Services\SearchableImporter;

class SynAppsSearchableSetup extends Command
{
    protected $signature = 'synapps:searchable-setup';

    protected $description = 'Enable global search: create the searchables table and (re)import backend nav and module searchables';

    public function handle(): int
    {
        $this->call('migrate', [
            '--path' => dirname(__DIR__, 3).'/database/migrations/searchable',
            '--realpath' => true,
            '--force' => true,
        ]);

        $importer = new SearchableImporter;
        $rows = $importer->rows($this->locales());
        synsearchable()->replaceSystemEntries($rows);

        foreach ($importer->warnings as $warning) {
            $this->components->warn($warning);
        }

        $this->table(
            ['Source', 'Rows'],
            collect($rows)->countBy('source')->map(fn (int $count, string $source) => [$source, $count])->values()->all()
        );

        $this->components->success('Searchable is set up: '.count($rows).' entries imported.');

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function locales(): array
    {
        /** @var list<string> $enabled */
        $enabled = config('synapps.apps.multi_language') ? config('synapps.apps.enabled_languages', []) : [];

        return $enabled !== [] ? $enabled : [(string) config('app.locale')];
    }
}
