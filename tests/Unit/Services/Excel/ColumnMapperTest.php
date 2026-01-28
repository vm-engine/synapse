<?php

declare(strict_types=1);

use VmEngine\Synapse\Services\Excel\Support\ColumnMapper;

describe('ColumnMapper', function () {
    it('returns raw row when no mapping is set', function () {
        $mapper = new ColumnMapper;
        $row = ['John', 'john@example.com', '123'];

        $result = $mapper->map($row, 1);

        expect($result)->toBe(['John', 'john@example.com', '123']);
    });

    it('maps with sequential array (positional mapping)', function () {
        $mapper = new ColumnMapper;
        $mapper->setMapping(['name', 'email', 'phone']);

        $row = ['John', 'john@example.com', '123'];
        $result = $mapper->map($row, 1);

        expect($result)->toBe([
            'name' => 'John',
            'email' => 'john@example.com',
            'phone' => '123',
        ]);
    });

    it('maps with associative array (explicit column index)', function () {
        $mapper = new ColumnMapper;
        $mapper->setMapping(['name' => 0, 'email' => 2, 'phone' => 4]);

        $row = ['John', 'skip1', 'john@example.com', 'skip2', '123'];
        $result = $mapper->map($row, 1);

        expect($result)->toBe([
            'name' => 'John',
            'email' => 'john@example.com',
            'phone' => '123',
        ]);
    });

    it('maps with closure', function () {
        $mapper = new ColumnMapper;
        $mapper->setMapping(fn (array $row, int $i) => [
            'full_name' => $row[0].' '.$row[1],
            'email' => strtolower($row[2]),
        ]);

        $row = ['John', 'Doe', 'JOHN@EXAMPLE.COM'];
        $result = $mapper->map($row, 1);

        expect($result)->toBe([
            'full_name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
    });

    it('handles missing columns gracefully in sequential mapping', function () {
        $mapper = new ColumnMapper;
        $mapper->setMapping(['name', 'email', 'phone', 'address']);

        $row = ['John', 'john@example.com'];
        $result = $mapper->map($row, 1);

        expect($result)->toBe([
            'name' => 'John',
            'email' => 'john@example.com',
            'phone' => null,
            'address' => null,
        ]);
    });

    it('handles missing columns gracefully in associative mapping', function () {
        $mapper = new ColumnMapper;
        $mapper->setMapping(['name' => 0, 'email' => 5]);

        $row = ['John', 'data'];
        $result = $mapper->map($row, 1);

        expect($result)->toBe([
            'name' => 'John',
            'email' => null,
        ]);
    });

    it('serializes array mapping for storage', function () {
        $mapper = new ColumnMapper;
        $mapper->setMapping(['name', 'email', 'phone']);

        $serialized = $mapper->toSerializable();

        expect($serialized)->toBe(['name', 'email', 'phone']);
    });

    it('serializes associative mapping for storage', function () {
        $mapper = new ColumnMapper;
        $mapper->setMapping(['name' => 0, 'email' => 2]);

        $serialized = $mapper->toSerializable();

        expect($serialized)->toBe(['name' => 0, 'email' => 2]);
    });

    it('returns null when serializing closure mapping', function () {
        $mapper = new ColumnMapper;
        $mapper->setMapping(fn (array $row, int $i) => $row);

        $serialized = $mapper->toSerializable();

        expect($serialized)->toBeNull();
    });

    it('returns null when serializing with no mapping', function () {
        $mapper = new ColumnMapper;

        $serialized = $mapper->toSerializable();

        expect($serialized)->toBeNull();
    });

    it('creates from serialized data', function () {
        $mapper = ColumnMapper::fromSerializable(['name', 'email']);

        $row = ['John', 'john@example.com'];
        $result = $mapper->map($row, 1);

        expect($result)->toBe([
            'name' => 'John',
            'email' => 'john@example.com',
        ]);
    });

    it('creates empty mapper from null serialized data', function () {
        $mapper = ColumnMapper::fromSerializable(null);

        $row = ['John', 'john@example.com'];
        $result = $mapper->map($row, 1);

        expect($result)->toBe(['John', 'john@example.com']);
    });

    it('returns the current mapping via getMapping', function () {
        $mapper = new ColumnMapper;
        expect($mapper->getMapping())->toBeNull();

        $mapper->setMapping(['name', 'email']);
        expect($mapper->getMapping())->toBe(['name', 'email']);
    });
});
