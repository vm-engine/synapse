<?php

namespace VmEngine\Synapse\Services;

class ThemeService
{
    /**
     * Render theme CDN stylesheets for a specific layout type,
     * followed by Vite-compiled base + layout-specific CSS assets.
     */
    public function renderStyles(?string $layoutType = null): string
    {
        $layoutType = $layoutType ?? $this->getCurrentLayoutType();
        $styles = $this->getResources('css', $layoutType);

        $cdnOutput = collect($styles)
            ->map(fn ($url) => '<link rel="stylesheet" href="'.e($url).'">')
            ->join("\n    ");

        $viteOutput = $this->renderViteStyles($layoutType);

        return $cdnOutput.($cdnOutput && $viteOutput ? "\n    " : '').$viteOutput;
    }

    /**
     * Render theme CDN scripts for a specific layout type,
     * followed by Vite-compiled base + layout-specific JS assets.
     */
    public function renderScripts(?string $layoutType = null): string
    {
        $layoutType = $layoutType ?? $this->getCurrentLayoutType();
        $scripts = $this->getResources('js', $layoutType);

        $cdnOutput = collect($scripts)
            ->map(fn ($url) => '<script src="'.e($url).'"></script>')
            ->join("\n    ");

        $viteOutput = $this->renderViteScripts($layoutType);

        return $cdnOutput.($cdnOutput && $viteOutput ? "\n    " : '').$viteOutput;
    }

    /**
     * Render Vite-compiled stylesheet tags for the given layout type.
     * Loads base.css (shared) + {layoutType}.css (layout-specific).
     * Silently skips entries missing from the Vite manifest (e.g. during dev
     * before the split files are built, or for themes that haven't been split yet).
     */
    private function renderViteStyles(string $layoutType): string
    {
        $entries = [
            'resources/css/base.css',
            "resources/css/{$layoutType}.css",
        ];

        return $this->safeViteRender($entries);
    }

    /**
     * Render Vite-compiled script tags for the given layout type.
     * Loads base.js (shared) + {layoutType}.js (layout-specific).
     * Silently skips entries missing from the Vite manifest.
     */
    private function renderViteScripts(string $layoutType): string
    {
        $entries = [
            'resources/js/base.js',
            "resources/js/{$layoutType}.js",
        ];

        return $this->safeViteRender($entries);
    }

    /**
     * Render Vite asset tags for the given entry points, silently skipping
     * any entries that are not present in the Vite manifest.
     */
    private function safeViteRender(array $entries): string
    {
        /** @var \Illuminate\Foundation\Vite $vite */
        $vite = app(\Illuminate\Foundation\Vite::class);

        // Filter to only entries that exist in the manifest so that a missing
        // layout-specific file (e.g. guest.css before the split build) does not
        // throw a Vite manifest exception.
        $existingEntries = array_filter($entries, static function (string $entry) use ($vite): bool {
            try {
                $vite->asset($entry);

                return true;
            } catch (\Throwable) {
                return false;
            }
        });

        if (empty($existingEntries)) {
            return '';
        }

        try {
            return $vite(array_values($existingEntries))->toHtml();
        } catch (\Throwable) {
            return '';
        }
    }

    /**
     * Render theme CDN headers for a specific layout type
     */
    public function renderHeaders(?string $layoutType = null): string
    {
        $layoutType = $layoutType ?? $this->getCurrentLayoutType();

        return collect($this->getResources('headers', $layoutType))
            ->join("\n    ");
    }

    /**
     * Get CDN resources (css/js/headers) for a specific layout type
     */
    protected function getResources(string $type, string $layoutType): array
    {
        $config = config("synapps.apps.themes.{$type}", []);

        // Merge 'all' resources with layout-specific resources
        return array_merge(
            $config['all'] ?? [],
            $config[$layoutType] ?? []
        );
    }

    /**
     * Detect current layout type based on URL prefix
     */
    public function getCurrentLayoutType(): string
    {
        $path = request()->path();

        // Check if URL starts with backend prefix (from synapps-auth config)
        $backendPrefix = ltrim(config('synapps.auth.routes.backend_prefix', '/apps'), '/');

        if ($backendPrefix && str_starts_with($path, $backendPrefix)) {
            return 'app';
        }

        // Default to frontend layout
        return 'frontend';
    }

    /**
     * Resolve theme layout with fallback chain
     */
    public function resolveThemeLayout(string $layoutType): string
    {
        $activeTheme = config('synapps.apps.themes.active', 'default');
        $themePath = synapps_path('resources/themes/'.$activeTheme.'/layouts/'.$layoutType.'.blade.php');

        // Check if active theme has the layout
        if (file_exists($themePath)) {
            return 'theme::'.$layoutType;
        }

        // Fallback to fallback theme
        $fallbackTheme = config('synapps.apps.themes.fallback', 'default');
        $fallbackPath = synapps_path('resources/themes/'.$fallbackTheme.'/layouts/'.$layoutType.'.blade.php');

        if ($activeTheme !== $fallbackTheme && file_exists($fallbackPath)) {
            return 'theme-fallback::'.$layoutType;
        }

        // Ultimate fallback to synapps default layouts
        $defaultLayouts = [
            'app' => 'synapps::components.layouts.app',
            'frontend' => 'synapps::components.layouts.layout',
            'guest' => 'synapps::components.layouts.guest',
        ];

        return $defaultLayouts[$layoutType] ?? 'synapps::components.layouts.app';
    }

    /**
     * Get active theme name
     */
    public function getActiveTheme(): string
    {
        return config('synapps.apps.themes.active', 'default');
    }

    /**
     * Get absolute path to theme file/directory
     */
    public function getThemePath(string $path = '', ?string $theme = null): string
    {
        $theme = $theme ?? $this->getActiveTheme();

        return synapps_path('resources/themes/'.$theme.($path ? '/'.ltrim($path, '/') : ''));
    }

    public function getLayoutView(string $layoutType): string
    {
        return $this->resolveThemeLayout($layoutType);
    }
}
