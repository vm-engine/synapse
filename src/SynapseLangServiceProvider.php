<?php

namespace VmEngine\Synapse;

use Illuminate\Translation\FileLoader;
use Illuminate\Translation\TranslationServiceProvider;
use VmEngine\Synapse\Services\SynapseUtils;

class SynapseLangServiceProvider extends TranslationServiceProvider
{
    protected function registerLoader()
    {
        if (SynapseUtils::isSynAppsAutoloaded()) {
            $this->app->singleton('translation.loader', function ($app) {
                return new FileLoader(
                    $app['files'],
                    config('synapse.lang_path.general', [])
                );
            });
        }
    }
}
