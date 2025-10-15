<?php

use VmEngine\Synapse\Components\Breadcrumbs;
use VmEngine\Synapse\Components\Checkboxes;
use VmEngine\Synapse\Components\RadioButtons;
use VmEngine\Synapse\Components\Select;
use VmEngine\Synapse\Components\MultiSelect;
use VmEngine\Synapse\Components\Password;
use VmEngine\Synapse\Components\Currency;
use VmEngine\Synapse\Components\Datepicker;
use VmEngine\Synapse\Components\ConfirmDialog;
use VmEngine\Synapse\Components\Drawer;
use VmEngine\Synapse\Components\Toastr;

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

describe('Select Component', function () {
    it('can be instantiated', function () {
        $component = new Select(options: ['option1', 'option2']);

        expect($component)->toBeInstanceOf(Select::class);
    });

    it('stores options property', function () {
        $options = ['option1', 'option2', 'option3'];
        $component = new Select(options: $options);

        expect($component->options)->toBe($options);
    });

    it('accepts empty options', function () {
        $component = new Select(options: []);

        expect($component->options)->toBe([]);
    });
});

describe('MultiSelect Component', function () {
    it('can be instantiated', function () {
        $component = new MultiSelect(
            name: 'tags',
            options: ['tag1', 'tag2'],
            model: []
        );

        expect($component)->toBeInstanceOf(MultiSelect::class);
    });

    it('stores name property', function () {
        $component = new MultiSelect(
            name: 'tags',
            options: [],
            model: []
        );

        expect($component->name)->toBe('tags');
    });

    it('stores options and model', function () {
        $options = ['tag1', 'tag2', 'tag3'];
        $model = ['tag1', 'tag2'];

        $component = new MultiSelect(
            name: 'test',
            options: $options,
            model: $model
        );

        expect($component->options)->toBe($options)
            ->and($component->model)->toBe($model);
    });

    it('defaults model to empty array', function () {
        $component = new MultiSelect(
            name: 'test',
            options: ['opt1'],
        );

        expect($component->model)->toBe([]);
    });
});

describe('Password Component', function () {
    it('can be instantiated', function () {
        $component = new Password();

        expect($component)->toBeInstanceOf(Password::class);
    });

    it('has render method', function () {
        $component = new Password();

        expect(method_exists($component, 'render'))->toBeTrue();
    });
});

describe('Currency Component', function () {
    it('can be instantiated', function () {
        $component = new Currency();

        expect($component)->toBeInstanceOf(Currency::class);
    });

    it('defaults to Rp. currency', function () {
        $component = new Currency();

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
        $component = new Datepicker();

        expect($component)->toBeInstanceOf(Datepicker::class);
    });

    it('defaults to empty flatpickr config', function () {
        $component = new Datepicker();

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
        $component = new ConfirmDialog();

        expect($component)->toBeInstanceOf(ConfirmDialog::class);
    });

    it('uses default title', function () {
        $component = new ConfirmDialog();

        expect($component->title)->toBe('Confirm Action');
    });

    it('uses default message', function () {
        $component = new ConfirmDialog();

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
        $component = new ConfirmDialog();

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
        $component = new ConfirmDialog();

        expect($component->confirmColor)->toBe('danger');
    });

    it('accepts custom icon', function () {
        $component = new ConfirmDialog(icon: 'fa-trash');

        expect($component->icon)->toBe('fa-trash');
    });

    it('uses default icon', function () {
        $component = new ConfirmDialog();

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
        expect(fn() => new Drawer())->toThrow(ArgumentCountError::class);
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
        $component = new Toastr();

        expect($component)->toBeInstanceOf(Toastr::class);
    });

    it('has render method', function () {
        $component = new Toastr();

        expect(method_exists($component, 'render'))->toBeTrue();
    });
});
