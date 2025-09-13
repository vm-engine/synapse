<?php

namespace VmEngine\Synapse;

use Illuminate\Translation\TranslationServiceProvider;
use VmEngine\Synapse\Services\LangFileLoader;

class SynapseLangServiceProvider extends TranslationServiceProvider
{
    protected function registerLoader()
    {
        $this->app->singleton('translation.loader', function ($app) {
            return new LangFileLoader($app['files'], config('synapse.lang_path.general'));
        });
    }
}
