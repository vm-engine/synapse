<?php

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class SynAppsMakeTheme extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'synapps:make-theme {name} {--copy-from=default : Theme to copy from}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new theme from template';

    public function handle(): int
    {
        $name = Str::slug($this->argument('name'));
        $copyFrom = $this->option('copy-from');

        $themePath = synapps_path("resources/themes/{$name}");
        $sourcePath = synapps_path("resources/themes/{$copyFrom}");

        // Check if theme already exists
        if (is_dir($themePath)) {
            $this->components->error("Theme '{$name}' already exists!");

            return 1;
        }

        // Check if source theme exists
        if (! is_dir($sourcePath)) {
            $this->components->error("Source theme '{$copyFrom}' not found!");
            $this->components->warn("Make sure the source theme exists in: {$sourcePath}");

            return 1;
        }

        // Copy theme structure
        $this->components->info("Creating theme '{$name}' from '{$copyFrom}'...");
        $this->copyDirectory($sourcePath, $themePath);

        // Update theme.json
        $this->updateThemeJson($themePath, $name);

        $this->components->success("Theme '{$name}' created successfully!");
        $this->components->info("Location: {$themePath}");
        $this->newLine();
        $this->components->warn('Next steps:');
        $this->line("  1. Edit {$themePath}/theme.json");
        $this->line("  2. Customize {$themePath}/css/theme.css");
        $this->line("  3. Update {$themePath}/layouts/*.blade.php");
        $this->line("  4. Update synapps/config/apps.json to set active theme:");
        $this->line('     { "themes": { "active": "'.$name.'" } }');
        $this->line('  5. Run: php artisan synapps:config');
        $this->line('  6. Run: npm run build');

        return 0;
    }

    /**
     * Copy directory recursively
     */
    protected function copyDirectory(string $source, string $destination): void
    {
        if (! is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $target = $destination.DIRECTORY_SEPARATOR.$iterator->getSubPathName();

            if ($item->isDir()) {
                if (! is_dir($target)) {
                    mkdir($target, 0755, true);
                }
            } else {
                copy($item, $target);
            }
        }
    }

    /**
     * Update theme.json with new theme information
     */
    protected function updateThemeJson(string $themePath, string $name): void
    {
        $jsonPath = $themePath.'/theme.json';

        if (! file_exists($jsonPath)) {
            return;
        }

        $config = json_decode(file_get_contents($jsonPath), true);
        $config['id'] = $name;
        $config['name'] = Str::title(str_replace('-', ' ', $name)).' Theme';
        $config['version'] = '1.0.0';
        $config['author'] = '';

        file_put_contents($jsonPath, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}
