<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\Components\Alert;
use VmEngine\Synapse\Components\Checkboxes;
use VmEngine\Synapse\Components\ConfirmDialog;
use VmEngine\Synapse\Components\Currency;
use VmEngine\Synapse\Components\Datepicker;
use VmEngine\Synapse\Components\Drawer;
use VmEngine\Synapse\Components\Password;
use VmEngine\Synapse\Components\PasswordGenerator;
use VmEngine\Synapse\Components\RadioButtons;
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

        expect($component->icon)->toBe('fa-solid fa-circle-exclamation');
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
            'success' => 'fa-solid fa-circle-check',
            'warning' => 'fa-solid fa-triangle-exclamation',
            'danger' => 'fa-solid fa-circle-exclamation',
            'info' => 'fa-solid fa-circle-info',
        ];

        foreach ($expectedIcons as $type => $expectedIcon) {
            $component = new Alert(type: $type);
            expect($component->icon)->toBe($expectedIcon);
        }
    });

    it('auto-assigns icon for error type as danger icon', function () {
        $component = new Alert(type: 'error');

        expect($component->icon)->toBe('fa-solid fa-circle-exclamation');
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
