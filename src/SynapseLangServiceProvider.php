<?php

namespace VmEngine\Synapse;

use Illuminate\Translation\TranslationServiceProvider;
use VmEngine\Synapse\Services\SynapseUtils;

class SynapseLangServiceProvider extends TranslationServiceProvider
{
    // public function register()
    // {
    //     if (SynapseUtils::isSynAppsAutoloaded()) {
    //         $langPath = config('synapse.lang_path.general', []);
    //         $that = $this;
    //         array_walk(
    //             $langPath,
    //             function ($v) use ($that) {
    //                 $that->loadTranslationsFrom($v[0], $v[1]);
    //             }
    //         );
    //     }
    // }

    // protected function registerLoader()
    // {
    //     if (SynapseUtils::isSynAppsAutoloaded()) {
    //         $langPath = config('synapse.lang_path.general', []);
    //         $that = $this;
    //         array_walk(
    //             $langPath,
    //             function ($v) use ($that) {
    //                 $that->loadTranslationsFrom($v[0], $v[1]);
    //             }
    //         );
    //         // $this->app->singleton('translation.loader', function ($app) {
    //         //     return new FileLoader(
    //         //         $app['files'],
    //         //         config('synapse.lang_path.general', [])
    //         //     );
    //         // });
    //     }
    // }
}
