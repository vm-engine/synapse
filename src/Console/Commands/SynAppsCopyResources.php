<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;
use VmEngine\Synapse\Services\SynapseUtils;

class SynAppsCopyResources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'synapps:copy-resources
                            {--css : Copy CSS files only}
                            {--js : Copy JS files only}
                            {--layouts : Copy layout files (includes nav components)}
                            {--assets : Copy static assets (images, webfonts)}
                            {--all : Copy everything (default if no options specified)}
                            {--force : Overwrite existing files without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-copy layout files, CSS, JS, and assets from Synapse stubs to synapps/resources';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Determine what to copy
        $copyAll = $this->option('all') || ! $this->hasAnyOption();
        $copyCss = $copyAll || $this->option('css');
        $copyJs = $copyAll || $this->option('js');
        $copyLayouts = $copyAll || $this->option('layouts');
        $copyAssets = $copyAll || $this->option('assets');
        $force = (bool) $this->option('force');

        // Check if synapps directory exists
        if (! file_exists(base_path('synapps'))) {
            $this->components->error('synapps directory not found. Please run synapps:setup first.');

            return 1;
        }

        // Source directory
        $stubsPath = realpath(__DIR__.DS.'..'.DS.'..'.DS.'..'.DS.'stubs'.DS.'synapps'.DS.'resources');
        if (! $stubsPath || ! is_dir($stubsPath)) {
            $this->components->error('Stubs directory not found at expected location.');

            return 1;
        }

        // Destination directory
        $destPath = base_path('synapps'.DS.'resources');

        // Create destination if it doesn't exist
        if (! file_exists($destPath)) {
            mkdir($destPath, 0755, true);
        }

        $copied = false;

        // Copy CSS files
        if ($copyCss) {
            $copied |= $this->copyResource(
                $stubsPath.DS.'css',
                $destPath.DS.'css',
                'CSS files',
                $force
            );
        }

        // Copy JS files
        if ($copyJs) {
            $copied |= $this->copyResource(
                $stubsPath.DS.'js',
                $destPath.DS.'js',
                'JavaScript files',
                $force
            );
        }

        // Copy layout files (includes nav components)
        if ($copyLayouts) {
            $copied |= $this->copyResource(
                $stubsPath.DS.'views'.DS.'layouts',
                $destPath.DS.'views'.DS.'layouts',
                'Layout files',
                $force
            );

            $copied |= $this->copyResource(
                $stubsPath.DS.'views'.DS.'components'.DS.'layouts',
                $destPath.DS.'views'.DS.'components'.DS.'layouts',
                'Component layout files',
                $force
            );

            $copied |= $this->copyResource(
                $stubsPath.DS.'views'.DS.'components'.DS.'nav',
                $destPath.DS.'views'.DS.'components'.DS.'nav',
                'Navigation components',
                $force
            );
        }

        // Copy assets
        if ($copyAssets) {
            $copied |= $this->copyResource(
                $stubsPath.DS.'assets',
                $destPath.DS.'assets',
                'Static assets',
                $force
            );
        }

        if ($copied) {
            $this->components->success('Resources copied successfully.');
            $this->components->info('Run "npm run build" or "composer run dev" to compile assets.');

            return 0;
        }

        $this->components->warn('No resources were copied.');

        return 0;
    }

    /**
     * Copy a resource directory
     */
    protected function copyResource(string $source, string $dest, string $label, bool $force): bool
    {
        if (! file_exists($source) || ! is_dir($source)) {
            $this->components->warn("Source not found: {$label}");

            return false;
        }

        // Check if destination is a symlink to source
        if (is_link($dest)) {
            $linkTarget = readlink($dest);
            $realSource = realpath($source);
            $realTarget = realpath(dirname($dest).DIRECTORY_SEPARATOR.$linkTarget);

            if ($realSource === $realTarget) {
                $this->components->info("{$label} already linked to source. Replacing symlink with real copy.");
                unlink($dest);
            } else {
                // Remove symlink
                unlink($dest);
            }
        }

        // Check if destination exists and has files
        if (! $force && file_exists($dest) && is_dir($dest) && count(scandir($dest)) > 2) {
            if (! $this->components->confirm("Overwrite existing {$label}?", false)) {
                $this->components->info("Skipped: {$label}");

                return false;
            }
        }

        // Create parent directory if needed
        $parentDir = dirname($dest);
        if (! file_exists($parentDir)) {
            mkdir($parentDir, 0755, true);
        }

        // The destination is merged, never cleared: these directories also hold host files the
        // stubs do not ship (the per-layout synapps-*.css / synapps-*.js entry points Vite builds
        // from), and wiping them silently broke the build. copyDirectory() replaces links in place,
        // so a hard-linked destination no longer writes through to the stub.

        // Copy directory
        if (SynapseUtils::copyDirectory($source, $dest)) {
            $this->components->info("Copied: {$label}");

            return true;
        }

        $this->components->error("Failed to copy: {$label}");

        return false;
    }

    /**
     * Check if any copy option is specified
     */
    protected function hasAnyOption(): bool
    {
        return $this->option('css')
            || $this->option('js')
            || $this->option('layouts')
            || $this->option('assets');
    }
}
