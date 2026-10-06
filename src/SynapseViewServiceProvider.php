<?php

declare(strict_types=1);

namespace VmEngine\Synapse;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use VmEngine\Synapse\Components\AdvSelect;
use VmEngine\Synapse\Components\Alert;
use VmEngine\Synapse\Components\Badge;
use VmEngine\Synapse\Components\Calendar;
use VmEngine\Synapse\Components\Checkboxes;
use VmEngine\Synapse\Components\ColorPicker;
use VmEngine\Synapse\Components\ConfirmDialog;
use VmEngine\Synapse\Components\Currency;
use VmEngine\Synapse\Components\Datepicker;
use VmEngine\Synapse\Components\DragNDrop;
use VmEngine\Synapse\Components\Drawer;
use VmEngine\Synapse\Components\ExcelExportButton;
use VmEngine\Synapse\Components\FileDrop;
use VmEngine\Synapse\Components\Kanban;
use VmEngine\Synapse\Components\KanbanCard;
use VmEngine\Synapse\Components\KanbanColumn;
use VmEngine\Synapse\Components\Lightbox;
use VmEngine\Synapse\Components\Modal;
use VmEngine\Synapse\Components\Panel;
use VmEngine\Synapse\Components\Password;
use VmEngine\Synapse\Components\PasswordGenerator;
use VmEngine\Synapse\Components\PerPageSelector;
use VmEngine\Synapse\Components\RadioButtons;
use VmEngine\Synapse\Components\SearchBox;
use VmEngine\Synapse\Components\SortIcon;
use VmEngine\Synapse\Components\SplitPane;
use VmEngine\Synapse\Components\StatTile;
use VmEngine\Synapse\Components\Steps;
use VmEngine\Synapse\Components\Toastr;
use VmEngine\Synapse\Components\Toggler;
use VmEngine\Synapse\Services\SynapseUtils;

class SynapseViewServiceProvider extends ServiceProvider
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
        if (config('livewire.component_layout') && SynapseUtils::isSynAppsAutoloaded()) {
            // Set dynamic layout based on URL prefix
            $this->setDynamicLayout();
        }
        $this->registerSynapseComponents();
    }

    public function boot(): void
    {
        $blade = app('blade.compiler');

        // Register Blade directives for theme resources
        $blade->directive('themeStyles', function ($layoutType) {
            return "<?php echo app('synapse.theme')->renderStyles({$layoutType}); ?>";
        });

        $blade->directive('themeScripts', function ($layoutType) {
            return "<?php echo app('synapse.theme')->renderScripts({$layoutType}); ?>";
        });

        $blade->directive('themeHeaders', function ($layoutType) {
            return "<?php echo app('synapse.theme')->renderHeaders({$layoutType}); ?>";
        });

        // Makes <livewire:synotif::bell /> and <livewire:synotif::list /> resolvable
        // from the MFC pairs under resources/views/components/notifications/.
        Livewire::addNamespace('synotif', __DIR__.'/../resources/views/components/notifications');

        // Makes <livewire:synsearch::palette /> resolvable from resources/views/components/searchable/.
        Livewire::addNamespace('synsearch', __DIR__.'/../resources/views/components/searchable');
    }

    /**
     * Register theme view paths with fallback mechanism
     */
    protected function registerThemeViews(): void
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
    protected function setDynamicLayout(): void
    {
        // Always use 'app' layout as default
        $layoutView = theme_service()->resolveThemeLayout('app');

        config(['livewire.component_layout' => $layoutView]);
    }

    private function registerSynapseComponents(): void
    {
        // Use app('blade.compiler') directly instead of the Blade facade to avoid
        // stale facade instance issue where the Facade root and the container-resolved
        // BladeCompiler diverge (especially during testing with Livewire v4).
        $blade = app('blade.compiler');
        $blade->component(Alert::class, 'synapse-alert');
        $blade->component(Badge::class, 'synapse-badge');
        $blade->component(Panel::class, 'synapse-panel');
        $blade->component(Drawer::class, 'synapse-drawer');
        $blade->component(Modal::class, 'synapse-modal');
        $blade->component(Lightbox::class, 'synapse-lightbox');
        $blade->component(AdvSelect::class, 'synapse-adv-select');
        $blade->component(Password::class, 'synapse-password');
        $blade->component(PasswordGenerator::class, 'synapse-password-generator');
        $blade->component(Currency::class, 'synapse-currency');
        $blade->component(RadioButtons::class, 'synapse-radio-buttons');
        $blade->component(Checkboxes::class, 'synapse-checkboxes');
        $blade->component(Datepicker::class, 'synapse-datepicker');
        $blade->component(Toggler::class, 'synapse-toggler');
        $blade->component(ConfirmDialog::class, 'synapse-confirm-dialog');
        $blade->component(Toastr::class, 'synapse-toastr');
        $blade->component(DragNDrop::class, 'synapse-drag-n-drop');
        $blade->component(FileDrop::class, 'synapse-file-drop');
        $blade->component(SortIcon::class, 'synapse-sort-icon');
        $blade->component(PerPageSelector::class, 'synapse-per-page-selector');
        $blade->component(ExcelExportButton::class, 'synapse-excel-export-button');
        $blade->component(SearchBox::class, 'synapse-search-box');
        $blade->component(StatTile::class, 'synapse-stat-tile');
        $blade->component(ColorPicker::class, 'synapse-color-picker');
        $blade->component(Steps::class, 'synapse-steps');
        $blade->component(SplitPane::class, 'synapse-split-pane');
        $blade->component(Kanban::class, 'synapse-kanban');
        $blade->component(KanbanColumn::class, 'synapse-kanban.column');
        $blade->component(KanbanCard::class, 'synapse-kanban.card');
        $blade->component(Calendar::class, 'synapse-calendar');
    }
}
