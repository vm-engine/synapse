<?php

declare(strict_types=1);

use VmEngine\Synapse\Console\Commands\SynAppsReconfig;
use VmEngine\Synapse\Console\Commands\SynAppsSetup;

/** Local to this file: test files run in separate processes under --parallel. */
function languageCommandSource(string $class): string
{
    return (string) file_get_contents((string) (new ReflectionClass($class))->getFileName());
}

it('defaults enabled languages to the app locale instead of a hardcoded en', function (string $class) {
    expect(languageCommandSource($class))
        ->toContain("\$enabledLanguages = [(string) config('app.locale') ?: 'en'];")
        ->not->toContain("\$enabledLanguages = ['en'];");
})->with([[SynAppsSetup::class], [SynAppsReconfig::class]]);

it('pre-fills the setup language prompt with the app locale', function () {
    expect(languageCommandSource(SynAppsSetup::class))
        ->toContain("default: implode(',', \$enabledLanguages),")
        ->not->toContain("default: 'en',");
});
