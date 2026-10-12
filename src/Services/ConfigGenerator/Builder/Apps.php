<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services\ConfigGenerator\Builder;

use VmEngine\Synapse\Services\ConfigDiff;
use VmEngine\Synapse\Services\ConfigGenerator\BuilderBase;

class Apps extends BuilderBase
{
    public function init(): void
    {
        $synappsPath = $this->synapps->synappsPath;

        if (! file_exists($synappsPath.'/config/apps.json')) {
            throw new \RuntimeException(
                "Synapse config file not found: {$synappsPath}/config/apps.json. ".
                'Run `php artisan synapps:setup` to initialise the project.'
            );
        }

        $this->synapps->config['apps'] = read_json_file($synappsPath.'/config/apps.json');
        if (! file_exists($synappsPath.'/config/apps.dev.json')) {
            file_put_contents($synappsPath.'/config/apps.dev.json', '{}');
        }

        $tmpEnvClient = read_json_file($synappsPath.'/config/apps.dev.json');
        $this->synapps->config['apps'] = ConfigDiff::merge(
            $this->synapps->config['apps'],
            $tmpEnvClient
        );
    }

    public function finalize(): void
    {
        // Load and merge active theme configuration
        $this->loadActiveThemeConfig();

        file_put_contents(
            $this->synapps->synappsPath.'/config/compiled/apps.json',
            json_encode($this->synapps->config['apps'], JSON_PRETTY_PRINT)
        );
        app('config')->set('app.name', $this->synapps->config['apps']['app_name']);
    }

    protected function loadActiveThemeConfig(): void
    {
        // Get active theme name
        $activeTheme = $this->synapps->config['apps']['themes']['active'] ?? 'default';
        $themePath = $this->synapps->synappsPath.'/resources/themes/'.$activeTheme;
        $themeJsonPath = $themePath.'/theme.json';

        // If active theme doesn't exist, try fallback theme
        if (! file_exists($themeJsonPath)) {
            $fallbackTheme = $this->synapps->config['apps']['themes']['fallback'] ?? 'default';
            if ($fallbackTheme !== $activeTheme) {
                $themePath = $this->synapps->synappsPath.'/resources/themes/'.$fallbackTheme;
                $themeJsonPath = $themePath.'/theme.json';
            }
        }

        // Load theme configuration if it exists
        if (file_exists($themeJsonPath)) {
            $themeConfig = read_json_file($themeJsonPath);

            // Merge theme CDN resources into apps config. The set of layout
            // buckets is read from the theme.json itself so custom layouts
            // (e.g. 'landing', 'minisite') don't get silently dropped.
            foreach (['css', 'js', 'headers'] as $type) {
                $layouts = is_array($themeConfig[$type] ?? null)
                    ? array_keys($themeConfig[$type])
                    : [];

                foreach ($layouts as $layout) {
                    if (! is_array($themeConfig[$type][$layout])) {
                        continue;
                    }

                    $this->synapps->config['apps']['themes'][$type][$layout] = array_merge(
                        $this->synapps->config['apps']['themes'][$type][$layout] ?? [],
                        $themeConfig[$type][$layout]
                    );
                }
            }

            // Store theme metadata for reference
            $this->synapps->config['apps']['themes']['metadata'] = [
                'id' => $themeConfig['id'] ?? $activeTheme,
                'name' => $themeConfig['name'] ?? $activeTheme,
                'version' => $themeConfig['version'] ?? '1.0.0',
                'author' => $themeConfig['author'] ?? '',
                'framework' => $themeConfig['framework'] ?? 'tailwind',
                'layouts' => $themeConfig['layouts'] ?? ['app', 'frontend'],
            ];
        }
    }
}
