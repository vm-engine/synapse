<?php

declare(strict_types=1);

use VmEngine\Synapse\Services\Helper\Breadcrumbs;

describe('Breadcrumbs', function () {
    it('can be instantiated with make method', function () {
        $breadcrumbs = Breadcrumbs::make('Home', '/');

        expect($breadcrumbs)->toBeInstanceOf(Breadcrumbs::class);
    });

    it('adds initial breadcrumb on make', function () {
        $breadcrumbs = Breadcrumbs::make('Home', '/', 'fa-home');

        expect($breadcrumbs->count())->toBe(1)
            ->and($breadcrumbs->items())->toHaveCount(1);
    });

    it('can add multiple breadcrumbs', function () {
        $breadcrumbs = Breadcrumbs::make('Home', '/')
            ->add('Users', '/users')
            ->add('Profile', '/users/1');

        expect($breadcrumbs->count())->toBe(3);
    });

    it('stores breadcrumb data correctly', function () {
        $breadcrumbs = Breadcrumbs::make('Home', '/', 'fa-home')
            ->add('Users', '/users', 'fa-users');

        $items = $breadcrumbs->items();

        expect($items[0])->toBe([
            'label' => 'Home',
            'url' => '/',
            'icon' => 'fa-home',
        ])->and($items[1])->toBe([
            'label' => 'Users',
            'url' => '/users',
            'icon' => 'fa-users',
        ]);
    });

    it('allows null icon', function () {
        $breadcrumbs = Breadcrumbs::make('Home', '/');

        expect($breadcrumbs->items()[0]['icon'])->toBeNull();
    });

    it('uses default hash for URL when not provided', function () {
        $breadcrumbs = Breadcrumbs::make('Home');

        expect($breadcrumbs->items()[0]['url'])->toBe('#');
    });

    it('returns true for hasItems when items exist', function () {
        $breadcrumbs = Breadcrumbs::make('Home', '/');

        expect($breadcrumbs->hasItems())->toBeTrue();
    });

    it('supports method chaining', function () {
        $breadcrumbs = Breadcrumbs::make('Home', '/')
            ->add('Level 1', '/level1')
            ->add('Level 2', '/level2')
            ->add('Level 3', '/level3');

        expect($breadcrumbs)->toBeInstanceOf(Breadcrumbs::class)
            ->and($breadcrumbs->count())->toBe(4);
    });

    it('returns all items as array', function () {
        $breadcrumbs = Breadcrumbs::make('Home', '/')
            ->add('About', '/about');

        $items = $breadcrumbs->items();

        expect($items)->toBeArray()
            ->and($items)->toHaveCount(2);
    });

    it('maintains insertion order', function () {
        $breadcrumbs = Breadcrumbs::make('First', '/first')
            ->add('Second', '/second')
            ->add('Third', '/third');

        $items = $breadcrumbs->items();

        expect($items[0]['label'])->toBe('First')
            ->and($items[1]['label'])->toBe('Second')
            ->and($items[2]['label'])->toBe('Third');
    });

    it('handles special characters in labels', function () {
        $breadcrumbs = Breadcrumbs::make('Home & Away', '/')
            ->add('Users <Admin>', '/users');

        expect($breadcrumbs->items()[0]['label'])->toBe('Home & Away')
            ->and($breadcrumbs->items()[1]['label'])->toBe('Users <Admin>');
    });

    it('handles empty strings', function () {
        $breadcrumbs = Breadcrumbs::make('', '');

        expect($breadcrumbs->items()[0]['label'])->toBe('')
            ->and($breadcrumbs->items()[0]['url'])->toBe('');
    });

    it('can create complex navigation paths', function () {
        $breadcrumbs = Breadcrumbs::make('Dashboard', '/', 'fa-dashboard')
            ->add('Settings', '/settings', 'fa-cog')
            ->add('Profile', '/settings/profile', 'fa-user')
            ->add('Security', '/settings/profile/security', 'fa-lock');

        expect($breadcrumbs->count())->toBe(4)
            ->and($breadcrumbs->items()[3]['label'])->toBe('Security')
            ->and($breadcrumbs->items()[3]['icon'])->toBe('fa-lock');
    });
});

it('returns the last crumb label as the current page', function () {
    expect(Breadcrumbs::make('Clients', '/clients')->add('Acme')->current())->toBe('Acme')
        ->and(Breadcrumbs::make('Clients')->current())->toBe('Clients');
});
