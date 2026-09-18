<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\Components\Alert;
use VmEngine\Synapse\Components\Badge;
use VmEngine\Synapse\Components\Checkboxes;
use VmEngine\Synapse\Components\ConfirmDialog;
use VmEngine\Synapse\Components\Currency;
use VmEngine\Synapse\Components\Datepicker;
use VmEngine\Synapse\Components\Drawer;
use VmEngine\Synapse\Components\Lightbox;
use VmEngine\Synapse\Components\Panel;
use VmEngine\Synapse\Components\Password;
use VmEngine\Synapse\Components\PasswordGenerator;
use VmEngine\Synapse\Components\RadioButtons;
use VmEngine\Synapse\Components\SearchBox;
use VmEngine\Synapse\Components\StatTile;
use VmEngine\Synapse\Components\Toastr;
use VmEngine\Synapse\Components\Toggler;

describe('Checkboxes Component', function () {
    it('can be instantiated', function () {
        $component = new Checkboxes(
            name: 'permissions',
            options: ['read', 'write', 'delete'],
            model: ['read']
        );

        expect($component)->toBeInstanceOf(Checkboxes::class);
    });

    it('stores name property', function () {
        $component = new Checkboxes(
            name: 'permissions',
            options: [],
            model: []
        );

        expect($component->name)->toBe('permissions');
    });

    it('stores options property', function () {
        $options = ['read', 'write', 'delete'];
        $component = new Checkboxes(
            name: 'test',
            options: $options,
            model: []
        );

        expect($component->options)->toBe($options);
    });

    it('stores model property', function () {
        $model = ['read', 'write'];
        $component = new Checkboxes(
            name: 'test',
            options: ['read', 'write', 'delete'],
            model: $model
        );

        expect($component->model)->toBe($model);
    });

    it('defaults model to empty array', function () {
        $component = new Checkboxes(
            name: 'test',
            options: ['read', 'write'],
        );

        expect($component->model)->toBe([]);
    });

    it('has render method', function () {
        $component = new Checkboxes(
            name: 'test',
            options: ['read'],
            model: []
        );

        expect(method_exists($component, 'render'))->toBeTrue();
    });
});

describe('RadioButtons Component', function () {
    it('can be instantiated', function () {
        $component = new RadioButtons(
            name: 'status',
            options: ['active', 'inactive'],
            model: 'active'
        );

        expect($component)->toBeInstanceOf(RadioButtons::class);
    });

    it('stores name property', function () {
        $component = new RadioButtons(
            name: 'status',
            options: [],
            model: ''
        );

        expect($component->name)->toBe('status');
    });

    it('stores options property', function () {
        $options = ['active', 'inactive', 'pending'];
        $component = new RadioButtons(
            name: 'test',
            options: $options,
            model: ''
        );

        expect($component->options)->toBe($options);
    });

    it('stores model property', function () {
        $component = new RadioButtons(
            name: 'test',
            options: ['active', 'inactive'],
            model: 'active'
        );

        expect($component->model)->toBe('active');
    });

    it('defaults model to empty string', function () {
        $component = new RadioButtons(
            name: 'test',
            options: ['yes', 'no'],
        );

        expect($component->model)->toBe('');
    });
});

describe('Password Component', function () {
    it('can be instantiated', function () {
        $component = new Password;

        expect($component)->toBeInstanceOf(Password::class);
    });

    it('has render method', function () {
        $component = new Password;

        expect(method_exists($component, 'render'))->toBeTrue();
    });
});

describe('Currency Component', function () {
    it('can be instantiated', function () {
        $component = new Currency;

        expect($component)->toBeInstanceOf(Currency::class);
    });

    it('defaults to Rp. currency', function () {
        $component = new Currency;

        expect($component->currency)->toBe('Rp.');
    });

    it('accepts custom currency symbol', function () {
        $component = new Currency(currency: '$');

        expect($component->currency)->toBe('$');
    });

    it('accepts various currency symbols', function () {
        $symbols = ['$', '€', '£', '¥', 'Rp.'];

        foreach ($symbols as $symbol) {
            $component = new Currency(currency: $symbol);
            expect($component->currency)->toBe($symbol);
        }
    });
});

describe('Datepicker Component', function () {
    it('can be instantiated', function () {
        $component = new Datepicker;

        expect($component)->toBeInstanceOf(Datepicker::class);
    });

    it('defaults to empty flatpickr config', function () {
        $component = new Datepicker;

        expect($component->flatpickr)->toBe('{}');
    });

    it('accepts flatpickr configuration', function () {
        $config = '{"dateFormat": "Y-m-d"}';
        $component = new Datepicker(flatpickr: $config);

        expect($component->flatpickr)->toBe($config);
    });

    it('accepts complex flatpickr configuration', function () {
        $config = '{"dateFormat": "Y-m-d", "enableTime": true, "time_24hr": true}';
        $component = new Datepicker(flatpickr: $config);

        expect($component->flatpickr)->toBe($config);
    });
});

describe('ConfirmDialog Component', function () {
    it('can be instantiated', function () {
        $component = new ConfirmDialog;

        expect($component)->toBeInstanceOf(ConfirmDialog::class);
    });

    it('uses default title', function () {
        $component = new ConfirmDialog;

        expect($component->title)->toBe('Confirm Action');
    });

    it('uses default message', function () {
        $component = new ConfirmDialog;

        expect($component->message)->toBe('Are you sure you want to proceed?');
    });

    it('accepts custom title and message', function () {
        $component = new ConfirmDialog(
            title: 'Delete Item',
            message: 'This cannot be undone'
        );

        expect($component->title)->toBe('Delete Item')
            ->and($component->message)->toBe('This cannot be undone');
    });

    it('accepts custom button text', function () {
        $component = new ConfirmDialog(
            confirmText: 'Delete',
            cancelText: 'Keep'
        );

        expect($component->confirmText)->toBe('Delete')
            ->and($component->cancelText)->toBe('Keep');
    });

    it('uses default button text', function () {
        $component = new ConfirmDialog;

        expect($component->confirmText)->toBe('Confirm')
            ->and($component->cancelText)->toBe('Cancel');
    });

    it('accepts custom confirm color', function () {
        $colors = ['danger', 'warning', 'success', 'primary'];

        foreach ($colors as $color) {
            $component = new ConfirmDialog(confirmColor: $color);
            expect($component->confirmColor)->toBe($color);
        }
    });

    it('uses default danger color', function () {
        $component = new ConfirmDialog;

        expect($component->confirmColor)->toBe('danger');
    });

    it('accepts custom icon', function () {
        $component = new ConfirmDialog(icon: 'fa-trash');

        expect($component->icon)->toBe('fa-trash');
    });

    it('uses default icon', function () {
        $component = new ConfirmDialog;

        expect($component->icon)->toBe('ph ph-warning-circle');
    });
});

describe('Drawer Component', function () {
    it('can be instantiated', function () {
        $component = new Drawer(title: 'Settings');

        expect($component)->toBeInstanceOf(Drawer::class);
    });

    it('stores title property', function () {
        $component = new Drawer(title: 'User Settings');

        expect($component->title)->toBe('User Settings');
    });

    it('requires title parameter', function () {
        // This would throw an error if title is not provided
        expect(fn () => new Drawer)->toThrow(ArgumentCountError::class);
    });

    it('accepts various title strings', function () {
        $titles = ['Settings', 'Profile', 'Edit User', 'Configuration Panel'];

        foreach ($titles as $title) {
            $component = new Drawer(title: $title);
            expect($component->title)->toBe($title);
        }
    });
});

describe('Toastr Component', function () {
    it('can be instantiated', function () {
        $component = new Toastr;

        expect($component)->toBeInstanceOf(Toastr::class);
    });

    it('has render method', function () {
        $component = new Toastr;

        expect(method_exists($component, 'render'))->toBeTrue();
    });
});

describe('Alert Component', function () {
    it('can be instantiated', function () {
        $component = new Alert;

        expect($component)->toBeInstanceOf(Alert::class);
    });

    it('defaults to info type', function () {
        $component = new Alert;

        expect($component->type)->toBe('info');
    });

    it('accepts all alert types', function () {
        $types = ['success', 'warning', 'danger', 'info'];

        foreach ($types as $type) {
            $component = new Alert(type: $type);
            expect($component->type)->toBe($type);
        }
    });

    it('normalizes error type to danger', function () {
        $component = new Alert(type: 'error');

        expect($component->type)->toBe('danger');
    });

    it('is dismissible by default', function () {
        $component = new Alert;

        expect($component->dismissible)->toBeTrue();
    });

    it('can be non-dismissible', function () {
        $component = new Alert(dismissible: false);

        expect($component->dismissible)->toBeFalse();
    });

    it('accepts custom title', function () {
        $component = new Alert(title: 'Custom Title');

        expect($component->title)->toBe('Custom Title');
    });

    it('accepts custom message', function () {
        $component = new Alert(message: 'Custom message content');

        expect($component->message)->toBe('Custom message content');
    });

    it('accepts both title and message', function () {
        $component = new Alert(
            title: 'Alert Title',
            message: 'Alert message'
        );

        expect($component->title)->toBe('Alert Title')
            ->and($component->message)->toBe('Alert message');
    });

    it('auto-assigns icon based on type', function () {
        $expectedIcons = [
            'success' => 'ph ph-check-circle',
            'warning' => 'ph ph-warning',
            'danger' => 'ph ph-warning-circle',
            'info' => 'ph ph-info',
        ];

        foreach ($expectedIcons as $type => $expectedIcon) {
            $component = new Alert(type: $type);
            expect($component->icon)->toBe($expectedIcon);
        }
    });

    it('auto-assigns icon for error type as danger icon', function () {
        $component = new Alert(type: 'error');

        expect($component->icon)->toBe('ph ph-warning-circle');
    });

    it('accepts custom icon', function () {
        $component = new Alert(
            type: 'success',
            icon: 'fa-solid fa-check'
        );

        expect($component->icon)->toBe('fa-solid fa-check');
    });

    it('allows empty icon', function () {
        $component = new Alert(icon: '');

        expect($component->icon)->toBe('');
    });

    it('defaults border to false', function () {
        $component = new Alert;

        expect($component->border)->toBeFalse();
    });

    it('accepts border option', function () {
        $component = new Alert(border: true);

        expect($component->border)->toBeTrue();
    });

    it('defaults solid to false', function () {
        $component = new Alert;

        expect($component->solid)->toBeFalse();
    });

    it('accepts solid option', function () {
        $component = new Alert(solid: true);

        expect($component->solid)->toBeTrue();
    });

    it('accepts all props together', function () {
        $component = new Alert(
            type: 'warning',
            title: 'Warning Title',
            message: 'Warning message',
            dismissible: false,
            icon: 'fa-solid fa-exclamation',
            border: true,
            solid: true
        );

        expect($component->type)->toBe('warning')
            ->and($component->title)->toBe('Warning Title')
            ->and($component->message)->toBe('Warning message')
            ->and($component->dismissible)->toBeFalse()
            ->and($component->icon)->toBe('fa-solid fa-exclamation')
            ->and($component->border)->toBeTrue()
            ->and($component->solid)->toBeTrue();
    });

    it('has render method', function () {
        $component = new Alert;

        expect(method_exists($component, 'render'))->toBeTrue();
    });
});

describe('Toggler Component', function () {
    it('can be instantiated', function () {
        $component = new Toggler;

        expect($component)->toBeInstanceOf(Toggler::class);
    });

    it('has default values', function () {
        $component = new Toggler;

        expect($component->id)->toBe('')
            ->and($component->name)->toBe('')
            ->and($component->label)->toBe('')
            ->and($component->checked)->toBeFalse()
            ->and($component->activeColor)->toBe('green')
            ->and($component->inline)->toBeFalse();
    });

    it('stores id property', function () {
        $component = new Toggler(id: 'my-toggler');

        expect($component->id)->toBe('my-toggler');
    });

    it('stores name property', function () {
        $component = new Toggler(name: 'is_active');

        expect($component->name)->toBe('is_active');
    });

    it('stores label property', function () {
        $component = new Toggler(label: 'Active Status');

        expect($component->label)->toBe('Active Status');
    });

    it('stores checked property as true', function () {
        $component = new Toggler(checked: true);

        expect($component->checked)->toBeTrue();
    });

    it('stores checked property as false', function () {
        $component = new Toggler(checked: false);

        expect($component->checked)->toBeFalse();
    });

    it('stores activeColor property', function () {
        $component = new Toggler(activeColor: 'blue');

        expect($component->activeColor)->toBe('blue');
    });

    it('stores inline property as true', function () {
        $component = new Toggler(inline: true);

        expect($component->inline)->toBeTrue();
    });

    it('stores inline property as false', function () {
        $component = new Toggler(inline: false);

        expect($component->inline)->toBeFalse();
    });

    it('accepts all parameters', function () {
        $component = new Toggler(
            id: 'toggler-1',
            name: 'status',
            label: 'Enable Feature',
            checked: true,
            activeColor: 'purple',
            inline: true
        );

        expect($component->id)->toBe('toggler-1')
            ->and($component->name)->toBe('status')
            ->and($component->label)->toBe('Enable Feature')
            ->and($component->checked)->toBeTrue()
            ->and($component->activeColor)->toBe('purple')
            ->and($component->inline)->toBeTrue();
    });

    it('has render method', function () {
        $component = new Toggler;

        expect(method_exists($component, 'render'))->toBeTrue();
    });
});

describe('PasswordGenerator Component', function () {
    it('can be instantiated with single model', function () {
        $component = new PasswordGenerator(
            model: 'password',
            length: 12
        );

        expect($component)->toBeInstanceOf(PasswordGenerator::class);
    });

    it('can be instantiated with array model', function () {
        $component = new PasswordGenerator(
            model: ['password', 'password_confirmation'],
            length: 16
        );

        expect($component)->toBeInstanceOf(PasswordGenerator::class);
    });

    it('can be instantiated without model', function () {
        $component = new PasswordGenerator;

        expect($component)->toBeInstanceOf(PasswordGenerator::class);
    });

    it('stores string model property', function () {
        $component = new PasswordGenerator(model: 'password');

        expect($component->model)->toBe('password');
    });

    it('stores array model property', function () {
        $model = ['password', 'password_confirmation'];
        $component = new PasswordGenerator(model: $model);

        expect($component->model)->toBe($model);
    });

    it('stores null model property', function () {
        $component = new PasswordGenerator;

        expect($component->model)->toBeNull();
    });

    it('stores length property', function () {
        $component = new PasswordGenerator(length: 16);

        expect($component->length)->toBe(16);
    });

    it('enforces minimum length of 8', function () {
        $component = new PasswordGenerator(length: 5);

        expect($component->length)->toBe(8);
    });

    it('allows length greater than 8', function () {
        $component = new PasswordGenerator(length: 20);

        expect($component->length)->toBe(20);
    });

    it('defaults length to 12', function () {
        $component = new PasswordGenerator;

        expect($component->length)->toBe(12);
    });

    it('has render method', function () {
        $component = new PasswordGenerator;

        expect(method_exists($component, 'render'))->toBeTrue();
    });
});

describe('Semantic CSS classes', function () {
    beforeEach(function () {
        // SynapseViewServiceProvider (which registers the synapse:: view
        // namespace and the <x-synapse-*> component tags) only boots when
        // SynapseUtils::isSynAppsAutoloaded() is true, which this package's
        // TestCase deliberately doesn't set up. Register just what these two
        // tests need directly, without touching TestCase/production wiring.
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(Alert::class, 'synapse-alert');
        Blade::component(Drawer::class, 'synapse-drawer');
    });

    it('renders alert with syn-alert classes', function () {
        $html = Blade::render('<x-synapse-alert type="success" title="Saved" message="All good" />');

        expect($html)->toContain('syn-alert')
            ->toContain('syn-success')
            ->not->toContain('bg-green-50');
    });

    it('renders drawer with syn-drawer classes', function () {
        $html = Blade::render('<x-synapse-drawer title="Details">Body</x-synapse-drawer>');

        expect($html)->toContain('syn-drawer-backdrop')
            ->toContain('syn-drawer-panel')
            ->not->toContain('translate-x-full text-gray-500');
    });
});

describe('Badge Component', function () {
    it('can be instantiated', function () {
        $component = new Badge;

        expect($component)->toBeInstanceOf(Badge::class);
    });

    it('has render method', function () {
        $component = new Badge;

        expect(method_exists($component, 'render'))->toBeTrue();
    });

    it('defaults to gray color and md size with no icon', function () {
        $component = new Badge;

        expect($component->color)->toBe('gray')
            ->and($component->size)->toBe('md')
            ->and($component->icon)->toBeNull();
    });

    it('accepts all valid colors', function () {
        $colors = ['gray', 'brand', 'success', 'warning', 'danger', 'info'];

        foreach ($colors as $color) {
            $component = new Badge(color: $color);
            expect($component->color)->toBe($color);
        }
    });

    it('normalizes an invalid color to gray', function () {
        $component = new Badge(color: 'purple');

        expect($component->color)->toBe('gray');
    });

    it('accepts both valid sizes', function () {
        foreach (['sm', 'md'] as $size) {
            $component = new Badge(size: $size);
            expect($component->size)->toBe($size);
        }
    });

    it('normalizes an invalid size to md', function () {
        $component = new Badge(size: 'xl');

        expect($component->size)->toBe('md');
    });

    it('accepts a custom icon', function () {
        $component = new Badge(icon: 'fa-solid fa-check');

        expect($component->icon)->toBe('fa-solid fa-check');
    });
});

describe('Panel Component', function () {
    beforeEach(function () {
        // See the "Semantic CSS classes" describe block below for why this
        // manual registration is needed in this package's test environment.
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(Panel::class, 'synapse-panel');
    });

    it('can be instantiated', function () {
        $component = new Panel;

        expect($component)->toBeInstanceOf(Panel::class);
    });

    it('has render method', function () {
        $component = new Panel;

        expect(method_exists($component, 'render'))->toBeTrue();
    });

    it('defaults title to null', function () {
        $component = new Panel;

        expect($component->title)->toBeNull();
    });

    it('accepts a custom title', function () {
        $component = new Panel(title: 'Client List');

        expect($component->title)->toBe('Client List');
    });

    it('defaults to not collapsible and not collapsed', function () {
        $component = new Panel;

        expect($component->collapsible)->toBeFalse()
            ->and($component->collapsed)->toBeFalse();
    });

    it('accepts collapsible and collapsed flags', function () {
        $component = new Panel(collapsible: true, collapsed: true);

        expect($component->collapsible)->toBeTrue()
            ->and($component->collapsed)->toBeTrue();
    });

    it('renders without Alpine collapse attributes when not collapsible', function () {
        $html = Blade::render('<x-synapse-panel title="Filters">Body</x-synapse-panel>');

        expect($html)->not->toContain('x-collapse')
            ->not->toContain('x-data');
    });

    it('renders collapsed and starts open by default when collapsible', function () {
        $html = Blade::render('<x-synapse-panel title="Filters" collapsible>Body</x-synapse-panel>');

        expect($html)->toContain('x-collapse')
            ->toContain('open: true')
            ->toContain('syn-panel-chevron');
    });

    it('starts closed when collapsible and collapsed are both set', function () {
        $html = Blade::render('<x-synapse-panel title="Filters" collapsible collapsed>Body</x-synapse-panel>');

        expect($html)->toContain('open: false');
    });
});

describe('SearchBox Component', function () {
    beforeEach(function () {
        // See the "Semantic CSS classes" describe block below for why this
        // manual registration is needed in this package's test environment.
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(SearchBox::class, 'synapse-search-box');
    });

    it('can be instantiated', function () {
        $component = new SearchBox;

        expect($component)->toBeInstanceOf(SearchBox::class);
    });

    it('has render method', function () {
        $component = new SearchBox;

        expect(method_exists($component, 'render'))->toBeTrue();
    });

    it('defaults placeholder to null', function () {
        $component = new SearchBox;

        expect($component->placeholder)->toBeNull();
    });

    it('accepts a custom placeholder', function () {
        $component = new SearchBox(placeholder: 'Search clients...');

        expect($component->placeholder)->toBe('Search clients...');
    });

    it('renders the search icon on the left with no trailing markup by default', function () {
        $html = Blade::render('<x-synapse-search-box placeholder="Search..." />');

        expect($html)->toContain('syn-search-box-icon')
            ->toContain('ph-magnifying-glass')
            ->not->toContain('syn-search-box-trailing');
    });

    it('passes wire:model attributes through to the input', function () {
        $html = Blade::render('<x-synapse-search-box wire:model.live.debounce="q" />');

        expect($html)->toContain('wire:model.live.debounce="q"')
            ->toContain('syn-search-box-input');
    });

    it('renders the trailing slot when provided', function () {
        $html = Blade::render(<<<'BLADE'
            <x-synapse-search-box placeholder="Search...">
                <x-slot:trailing>
                    <button type="button">Filter</button>
                </x-slot:trailing>
            </x-synapse-search-box>
            BLADE);

        expect($html)->toContain('syn-search-box-trailing')
            ->toContain('syn-search-box-has-trailing')
            ->toContain('Filter');
    });
});

describe('StatTile Component', function () {
    beforeEach(function () {
        // See the "Semantic CSS classes" describe block below for why this
        // manual registration is needed in this package's test environment.
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(StatTile::class, 'synapse-stat-tile');
    });

    it('can be instantiated', function () {
        $component = new StatTile;

        expect($component)->toBeInstanceOf(StatTile::class);
    });

    it('has render method', function () {
        $component = new StatTile;

        expect(method_exists($component, 'render'))->toBeTrue();
    });

    it('defaults to gray color, bordered, no icon, no value color', function () {
        $component = new StatTile;

        expect($component->color)->toBe('gray')
            ->and($component->bordered)->toBeTrue()
            ->and($component->icon)->toBeNull()
            ->and($component->valueColor)->toBeNull();
    });

    it('accepts all valid colors', function () {
        $colors = ['gray', 'blue', 'indigo', 'purple', 'red', 'orange', 'green'];

        foreach ($colors as $color) {
            $component = new StatTile(color: $color);
            expect($component->color)->toBe($color);
        }
    });

    it('normalizes an invalid color to gray', function () {
        $component = new StatTile(color: 'teal');

        expect($component->color)->toBe('gray');
    });

    it('normalizes an invalid valueColor to null', function () {
        $component = new StatTile(valueColor: 'teal');

        expect($component->valueColor)->toBeNull();
    });

    it('accepts a valid valueColor', function () {
        $component = new StatTile(valueColor: 'red');

        expect($component->valueColor)->toBe('red');
    });

    it('renders the icon square only when an icon is given', function () {
        $withIcon = Blade::render('<x-synapse-stat-tile icon="fa-solid fa-file-invoice" color="blue" label="Total Invoices" value="2.613" />');
        $withoutIcon = Blade::render('<x-synapse-stat-tile label="Status" :bordered="false" />');

        expect($withIcon)->toContain('syn-stat-tile-icon-blue')
            ->toContain('fa-file-invoice');
        expect($withoutIcon)->not->toContain('syn-stat-tile-icon');
    });

    it('renders bordered by default and flat when bordered is false', function () {
        $bordered = Blade::render('<x-synapse-stat-tile label="Total Clients" value="675" />');
        $flat = Blade::render('<x-synapse-stat-tile label="Status" :bordered="false" />');

        expect($bordered)->toContain('syn-stat-tile-bordered');
        expect($flat)->toContain('syn-stat-tile-flat');
    });

    it('applies the valueColor class to the value line', function () {
        $html = Blade::render('<x-synapse-stat-tile label="Overdue Invoices" value="322" valueColor="orange" />');

        expect($html)->toContain('syn-stat-tile-value-orange');
    });

    it('renders slot content alongside the value', function () {
        $html = Blade::render(<<<'BLADE'
            <x-synapse-stat-tile label="Progress" value="42%" :bordered="false">
                <div class="h-2.5 rounded-full bg-blue-600" style="width: 42%"></div>
            </x-synapse-stat-tile>
            BLADE);

        expect($html)->toContain('syn-stat-tile-value')
            ->toContain('42%')
            ->toContain('bg-blue-600');
    });

    it('renders slot content in place of a missing value', function () {
        $html = Blade::render(<<<'BLADE'
            <x-synapse-stat-tile label="Status" :bordered="false">
                <span class="bg-green-100 text-green-800">Active</span>
            </x-synapse-stat-tile>
            BLADE);

        expect($html)->not->toContain('syn-stat-tile-value')
            ->toContain('Active');
    });

    it('defaults variant to tile and iconPosition to left', function () {
        $component = new StatTile;

        expect($component->variant)->toBe('tile')
            ->and($component->iconPosition)->toBe('left')
            ->and($component->iconBackground)->toBeTrue()
            ->and($component->tinted)->toBeFalse();
    });

    it('normalizes an invalid variant to tile', function () {
        $component = new StatTile(variant: 'bogus');

        expect($component->variant)->toBe('tile');
    });

    it('normalizes an invalid iconPosition to left', function () {
        $component = new StatTile(iconPosition: 'bogus');

        expect($component->iconPosition)->toBe('left');
    });

    it('normalizes an invalid sublabelColor to null', function () {
        $component = new StatTile(sublabelColor: 'teal');

        expect($component->sublabelColor)->toBeNull();
    });

    it('renders a bare icon with no background box when iconBackground is false', function () {
        $html = Blade::render('<x-synapse-stat-tile icon="fa-solid fa-check" color="green" label="X" value="1" :icon-background="false" />');

        expect($html)->toContain('syn-stat-tile-icon-bare');
    });

    it('renders the card variant with title, value and sublabel', function () {
        $html = Blade::render('<x-synapse-stat-tile variant="card" label="Stuck Transactions" value="4,277" sublabel="> 30 min old" />');

        expect($html)->toContain('syn-stat-tile-card')
            ->toContain('syn-stat-tile-card-title')
            ->toContain('syn-stat-tile-card-value')
            ->toContain('syn-stat-tile-card-sublabel')
            ->toContain('Stuck Transactions')
            ->toContain('4,277')
            ->toContain('30 min old');
    });

    it('renders the icon in the title row when iconPosition is title', function () {
        $html = Blade::render('<x-synapse-stat-tile variant="card" icon="fa-solid fa-hourglass-half" iconPosition="title" label="X" value="1" />');

        expect($html)->toContain('syn-stat-tile-card-icon-title')
            ->not->toContain('syn-stat-tile-card-row');
    });

    it('renders the icon beside a stacked column when iconPosition is left', function () {
        $html = Blade::render('<x-synapse-stat-tile variant="card" icon="fa-solid fa-hourglass-half" iconPosition="left" label="X" value="1" />');

        expect($html)->toContain('syn-stat-tile-card-row')
            ->not->toContain('syn-stat-tile-card-icon-title');
    });

    it('renders a cropped decorative icon when iconPosition is background', function () {
        $html = Blade::render('<x-synapse-stat-tile variant="card" icon="fa-solid fa-chart-line" iconPosition="background" color="blue" label="X" value="1" />');

        expect($html)->toContain('syn-stat-tile-card-icon-bg');
    });

    it('applies the tinted class and color when tinted is true', function () {
        $html = Blade::render('<x-synapse-stat-tile variant="card" color="red" tinted label="X" value="1" />');

        expect($html)->toContain('syn-stat-tile-card-tinted')
            ->toContain('syn-stat-tile-card-tinted-red');
    });

    it('does not apply the tinted class by default', function () {
        $html = Blade::render('<x-synapse-stat-tile variant="card" color="red" label="X" value="1" />');

        expect($html)->not->toContain('syn-stat-tile-card-tinted');
    });

    it('renders as an anchor and adds the clickable class when href is given', function () {
        $html = Blade::render('<x-synapse-stat-tile variant="card" href="/dashboard" label="X" value="1" />');

        expect($html)->toContain('<a')
            ->toContain('href="/dashboard"')
            ->toContain('syn-stat-tile-card-clickable');
    });

    it('adds the clickable class without href when clickable is true', function () {
        $html = Blade::render('<x-synapse-stat-tile variant="card" clickable label="X" value="1" />');

        expect($html)->toContain('<div')
            ->toContain('syn-stat-tile-card-clickable');
    });

    it('overrides label, value and sublabel with named slots', function () {
        $html = Blade::render(<<<'BLADE'
            <x-synapse-stat-tile variant="card" label="Fallback Label" value="0" sublabel="Fallback Sub">
                <x-slot:label><em>Slot Label</em></x-slot:label>
                <x-slot:value><strong>Slot Value</strong></x-slot:value>
                <x-slot:sublabel><small>Slot Sub</small></x-slot:sublabel>
            </x-synapse-stat-tile>
            BLADE);

        expect($html)->toContain('<em>Slot Label</em>')
            ->toContain('<strong>Slot Value</strong>')
            ->toContain('<small>Slot Sub</small>')
            ->not->toContain('Fallback Label')
            ->not->toContain('Fallback Sub');
    });
});

describe('Lightbox Component', function () {
    beforeEach(function () {
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(Lightbox::class, 'synapse-lightbox');
    });

    it('defaults name to lightbox', function () {
        expect((new Lightbox)->name)->toBe('lightbox');
    });

    it('renders syn-lightbox classes and name-scoped open/close events', function () {
        $html = Blade::render('<x-synapse-lightbox name="preview" />');

        expect($html)->toContain('syn-lightbox')
            ->toContain('syn-lightbox-frame')
            ->toContain('syn-lightbox-close')
            ->toContain('syn-lightbox-img')
            ->toContain('open-lightbox-preview')
            ->toContain('close-lightbox-preview')
            ->not->toContain('bg-black/80');
    });
});
