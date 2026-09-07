<?php

declare(strict_types=1);

use VmEngine\Synapse\Components\AdvSelect;

describe('AdvSelect Component', function () {
    it('can be instantiated with only the required wireModel', function () {
        $component = new AdvSelect(wireModel: 'selectedUser');

        expect($component)->toBeInstanceOf(AdvSelect::class);
    });

    it('stores the wireModel property', function () {
        $component = new AdvSelect(wireModel: 'role');

        expect($component->wireModel)->toBe('role');
    });

    it('defaults to single-select mode', function () {
        $component = new AdvSelect(wireModel: 'role');

        expect($component->multiple)->toBeFalse();
    });

    it('accepts multiple mode', function () {
        $component = new AdvSelect(wireModel: 'tags', multiple: true);

        expect($component->multiple)->toBeTrue();
    });

    it('defaults placeholder to "Select an option"', function () {
        $component = new AdvSelect(wireModel: 'role');

        expect($component->placeholder)->toBe('Select an option');
    });

    it('accepts a custom placeholder', function () {
        $component = new AdvSelect(wireModel: 'role', placeholder: 'Search users...');

        expect($component->placeholder)->toBe('Search users...');
    });

    it('defaults labelField and valueField to label/value', function () {
        $component = new AdvSelect(wireModel: 'role');

        expect($component->labelField)->toBe('label')
            ->and($component->valueField)->toBe('value');
    });

    it('defaults options to an empty array', function () {
        $component = new AdvSelect(wireModel: 'role');

        expect($component->options)->toBe([]);
    });

    it('accepts static options', function () {
        $options = [
            ['value' => 'admin', 'label' => 'Administrator'],
            ['value' => 'user', 'label' => 'User'],
        ];
        $component = new AdvSelect(wireModel: 'role', options: $options);

        expect($component->options)->toBe($options);
    });

    it('defaults preload to null', function () {
        $component = new AdvSelect(wireModel: 'role');

        expect($component->preload)->toBeNull();
    });

    it('defaults minSearchLength to 2', function () {
        $component = new AdvSelect(wireModel: 'role');

        expect($component->minSearchLength)->toBe(2);
    });

    it('defaults live, allowCreate to false and autoLoad to true', function () {
        $component = new AdvSelect(wireModel: 'role');

        expect($component->live)->toBeFalse()
            ->and($component->allowCreate)->toBeFalse()
            ->and($component->autoLoad)->toBeTrue();
    });

    it('has render method', function () {
        $component = new AdvSelect(wireModel: 'role');

        expect(method_exists($component, 'render'))->toBeTrue();
    });
});
