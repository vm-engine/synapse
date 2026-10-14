<?php

declare(strict_types=1);

use Illuminate\Support\Arr;

/** Stub modules that ship Indonesian translations, relative to the package root. */
const STUB_LANG_DIRS = [
    'settings' => 'stubs/modules/settings/lang',
    'home' => 'stubs/synapps/modules/home/lang',
];

dataset('stub lang files', function (): array {
    $cases = [];

    foreach (STUB_LANG_DIRS as $module => $dir) {
        foreach (glob(__DIR__.'/../../'.$dir.'/en/*.php') ?: [] as $path) {
            $cases["{$module} ".basename($path)] = [$dir, basename($path)];
        }
    }

    return $cases;
});

it('has an Indonesian line for every English key with the same placeholders', function (string $dir, string $file) {
    $idPath = __DIR__.'/../../'.$dir.'/id/'.$file;

    expect($idPath)->toBeFile();

    $en = Arr::dot(require __DIR__.'/../../'.$dir.'/en/'.$file);
    $id = Arr::dot(require $idPath);

    expect(array_keys($id))->toEqualCanonicalizing(array_keys($en));

    $placeholders = function (string $line): array {
        preg_match_all('/:[a-z_]+/', $line, $matches);
        sort($matches[0]);

        return $matches[0];
    };

    foreach ($en as $key => $line) {
        expect($placeholders($id[$key]))->toBe($placeholders($line), "{$file}: {$key}");
    }
})->with('stub lang files');
