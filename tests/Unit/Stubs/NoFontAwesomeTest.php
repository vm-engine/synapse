<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

it('uses Phosphor, not Font Awesome, in package views, classes and stubs', function () {
    $root = dirname(__DIR__, 3);
    $offenders = [];

    foreach (Finder::create()->files()->in(["$root/src", "$root/resources", "$root/stubs"])->name(['*.php', '*.json', '*.js']) as $file) {
        if (preg_match('/\bfa-(solid|regular|brands)\b|\bfa[srb]? fa-[a-z]|\bfa-spin(ner)?\b|\bfa-circle-notch\b/', $file->getContents()) === 1) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    sort($offenders);

    expect($offenders)->toBe([]);
});
