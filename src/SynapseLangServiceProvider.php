<?php

declare(strict_types=1);

namespace VmEngine\Synapse;

use Illuminate\Translation\FileLoader;
use Illuminate\Translation\TranslationServiceProvider;
use ReflectionClass;

/**
 * Owns the translator (it replaces Laravel's TranslationServiceProvider through package
 * discovery) so the package can ship group translations — validation, auth, passwords,
 * pagination — for locales Laravel itself only ships in English.
 *
 * Group files are looked up in order, each overriding the previous key by key
 * (FileLoader merges with array_replace_recursive): the framework's English, then
 * Synapse's lang/, then the project's lang/. A project overrides one message by
 * writing only that key; everything else still comes from Synapse.
 */
class SynapseLangServiceProvider extends TranslationServiceProvider
{
    /**
     * @return list<string>
     */
    public static function loaderPaths(string $projectLangPath): array
    {
        $frameworkLangPath = dirname((string) (new ReflectionClass(TranslationServiceProvider::class))->getFileName()).'/lang';

        return [$frameworkLangPath, dirname(__DIR__).'/lang', $projectLangPath];
    }

    protected function registerLoader()
    {
        $this->app->singleton('translation.loader', function ($app) {
            return new FileLoader($app['files'], self::loaderPaths($app['path.lang']));
        });
    }
}
