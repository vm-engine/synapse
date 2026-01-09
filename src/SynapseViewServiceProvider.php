<?php

namespace VmEngine\Synapse;

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewServiceProvider;
use VmEngine\Synapse\Components\AdvSelect;
use VmEngine\Synapse\Components\Alert;
use VmEngine\Synapse\Components\Checkboxes;
use VmEngine\Synapse\Components\ConfirmDialog;
use VmEngine\Synapse\Components\Currency;
use VmEngine\Synapse\Components\Datepicker;
use VmEngine\Synapse\Components\DragNDrop;
use VmEngine\Synapse\Components\Drawer;
use VmEngine\Synapse\Components\Modal;
use VmEngine\Synapse\Components\MultiSelect;
use VmEngine\Synapse\Components\Password;
use VmEngine\Synapse\Components\RadioButtons;
use VmEngine\Synapse\Components\Select;
use VmEngine\Synapse\Components\Toastr;
use VmEngine\Synapse\Components\Toggler;
use VmEngine\Synapse\Services\SynapseUtils;

class SynapseViewServiceProvider extends ViewServiceProvider
{
    public function register()
    {
        if (SynapseUtils::isSynAppsAutoloaded()) {
            // Register theme views BEFORE general views (higher priority)
            $this->registerThemeViews();

            foreach (config('synapse.view_path.general', []) as $path) {
                $this->loadViewsFrom($path[0], $path[1]);
            }
        }
        parent::register();
        if (config('livewire.layout') && SynapseUtils::isSynAppsAutoloaded()) {
            // Set dynamic layout based on URL prefix
            $this->setDynamicLayout();
        }
        $this->registerSynapseComponents();
    }

    public function boot()
    {
        // Register Blade directives for theme resources
        Blade::directive('themeStyles', function ($layoutType) {
            return "<?php echo app('synapse.theme')->renderStyles({$layoutType}); ?>";
        });

        Blade::directive('themeScripts', function ($layoutType) {
            return "<?php echo app('synapse.theme')->renderScripts({$layoutType}); ?>";
        });

        Blade::directive('themeHeaders', function ($layoutType) {
            return "<?php echo app('synapse.theme')->renderHeaders({$layoutType}); ?>";
        });
    }

    /**
     * Register theme view paths with fallback mechanism
     */
    protected function registerThemeViews()
    {
        $activeTheme = config('synapps.apps.themes.active', 'default');
        $fallbackTheme = config('synapps.apps.themes.fallback', 'default');

        $themePath = synapps_path('resources/themes/'.$activeTheme);
        $fallbackPath = synapps_path('resources/themes/'.$fallbackTheme);

        // Register active theme (highest priority)
        if (is_dir($themePath.'/layouts')) {
            $this->loadViewsFrom($themePath.'/layouts', 'theme');
        }

        // Register fallback theme
        if ($activeTheme !== $fallbackTheme && is_dir($fallbackPath.'/layouts')) {
            $this->loadViewsFrom($fallbackPath.'/layouts', 'theme-fallback');
        }
    }

    /**
     * Set default Livewire layout to app.blade.php
     */
    protected function setDynamicLayout()
    {
        // Always use 'app' layout as default
        $layoutView = $this->resolveThemeLayout('app');

        config(['livewire.layout' => $layoutView]);
    }

    /**
     * Resolve theme layout with fallback chain
     */
    protected function resolveThemeLayout(string $layoutType): string
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
        ];

        return $defaultLayouts[$layoutType] ?? 'synapps::components.layouts.app';
    }

    private function registerSynapseComponents()
    {
        Blade::component(Alert::class, 'synapse-alert');
        Blade::component(Drawer::class, 'synapse-drawer');
        Blade::component(Modal::class, 'synapse-modal');
        Blade::component(Select::class, 'synapse-select');
        Blade::component(MultiSelect::class, 'synapse-multiselect');
        Blade::component(AdvSelect::class, 'synapse-adv-select');
        Blade::component(Password::class, 'synapse-password');
        Blade::component(Currency::class, 'synapse-currency');
        Blade::component(RadioButtons::class, 'synapse-radio-buttons');
        Blade::component(Checkboxes::class, 'synapse-checkboxes');
        Blade::component(Datepicker::class, 'synapse-datepicker');
        Blade::component(Toggler::class, 'synapse-toggler');
        Blade::component(ConfirmDialog::class, 'synapse-confirm-dialog');
        Blade::component(Toastr::class, 'synapse-toastr');
        Blade::component(DragNDrop::class, 'synapse-drag-n-drop');
    }
}
