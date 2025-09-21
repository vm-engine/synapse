<?php

namespace VmEngine\Synapse;

use Illuminate\Translation\FileLoader;
use Illuminate\Translation\TranslationServiceProvider;

class SynapseLangServiceProvider extends TranslationServiceProvider
{
    protected function registerLoader()
    {
        $this->app->singleton('translation.loader', function ($app) {
            return new FileLoader(
                $app['files'],
                config('synapse.lang_path.general', [])
            );
        });
    }
}
