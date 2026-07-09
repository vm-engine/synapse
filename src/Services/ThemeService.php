<?php

namespace VmEngine\Synapse\Services;

class ThemeService
{
    /**
     * Render theme CDN stylesheets for a specific layout type
     */
    public function renderStyles(?string $layoutType = null): string
    {
        $layoutType = $layoutType ?? $this->getCurrentLayoutType();
        $styles = $this->getResources('css', $layoutType);

        return collect($styles)
            ->map(fn ($entry) => $this->renderLinkTag($entry))
            ->join("\n    ");
    }

    /**
     * Render theme CDN scripts for a specific layout type
     */
    public function renderScripts(?string $layoutType = null): string
    {
        $layoutType = $layoutType ?? $this->getCurrentLayoutType();
        $scripts = $this->getResources('js', $layoutType);

        return collect($scripts)
            ->map(fn ($entry) => $this->renderScriptTag($entry))
            ->join("\n    ");
    }

    /**
     * Render a single CDN <link rel="stylesheet"> tag. `$entry` may be a
     * plain URL string (backward compatible) or an array with 'href' (or
     * 'src'), 'integrity', and 'crossorigin' keys for Subresource Integrity.
     *
     * @param  string|array<string, string>  $entry
     */
    protected function renderLinkTag(string|array $entry): string
    {
        if (is_string($entry)) {
            return '<link rel="stylesheet" href="'.e($entry).'">';
        }

        $href = $entry['href'] ?? $entry['src'] ?? '';
        $tag = '<link rel="stylesheet" href="'.e($href).'"';

        if (! empty($entry['integrity'])) {
            $tag .= ' integrity="'.e($entry['integrity']).'"';
        }
        if (! empty($entry['crossorigin'])) {
            $tag .= ' crossorigin="'.e($entry['crossorigin']).'"';
        }

        return $tag.'>';
    }

    /**
     * Render a single CDN <script> tag. `$entry` may be a plain URL string
     * (backward compatible) or an array with 'src', 'integrity', and
     * 'crossorigin' keys for Subresource Integrity.
     *
     * @param  string|array<string, string>  $entry
     */
    protected function renderScriptTag(string|array $entry): string
    {
        if (is_string($entry)) {
            return '<script src="'.e($entry).'"></script>';
        }

        $src = $entry['src'] ?? '';
        $tag = '<script src="'.e($src).'"';

        if (! empty($entry['integrity'])) {
            $tag .= ' integrity="'.e($entry['integrity']).'"';
        }
        if (! empty($entry['crossorigin'])) {
            $tag .= ' crossorigin="'.e($entry['crossorigin']).'"';
        }

        return $tag.'></script>';
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
