<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Rte;

use Illuminate\Support\Facades\File;
use stdClass;

/**
 * The optional rich-text editor: whether it is on, which packages it needs, and how its
 * script is wired into the host. Switched by `synapps:setup` / `synapps:reconfig`
 * (`rte.enabled` in synapps/config/apps.json).
 */
final class RteSetup
{
    /** npm packages the editor needs. */
    public const NPM = ['jodit' => '^4.15'];

    /** Composer packages the sanitiser needs. */
    public const COMPOSER = ['mews/purifier' => '^3.4'];

    /** Line added to the host's resources/js/app.js while the editor is enabled. */
    public const APP_JS_IMPORT = "import '../../synapps/resources/js/synapse-rte.js';";

    public const DISABLED_MESSAGE = 'The rich-text editor is disabled. Enable it with "php artisan synapps:reconfig".';

    public static function enabled(): bool
    {
        return (bool) config('synapps.apps.rte.enabled', false);
    }

    /**
     * Add the editor's packages to an apps.json "dependencies" block, or remove them.
     * A version the host already pinned is kept. `synapps:discover` installs the block.
     *
     * @param  array<string, mixed>  $dependencies
     * @return array<string, mixed>
     */
    public static function applyDependencies(array $dependencies, bool $enabled): array
    {
        foreach (['npm' => self::NPM, 'composer' => self::COMPOSER] as $manager => $packages) {
            $main = (array) ($dependencies[$manager]['main'] ?? []);

            foreach ($packages as $package => $version) {
                if ($enabled) {
                    $main[$package] ??= $version;
                } else {
                    unset($main[$package]);
                }
            }

            if ($main !== []) {
                $dependencies[$manager]['main'] = $main;
            } elseif (! empty((array) ($dependencies[$manager]['main'] ?? []))) {
                // Emptied here: an empty PHP array would be written as a JSON list.
                $dependencies[$manager]['main'] = new stdClass;
            }
        }

        return $dependencies;
    }

    /**
     * Wire the editor's script into the host: copy synapse-rte.js when missing and add the
     * import to resources/js/app.js, or remove the import when disabled. The script is a file
     * of its own, outside synapse-components.js, because it imports Jodit, which a host
     * without the editor does not have installed.
     *
     * @return bool whether a file changed
     */
    public static function syncAssets(bool $enabled): bool
    {
        $changed = false;
        $script = base_path('synapps/resources/js/synapse-rte.js');

        if ($enabled && ! file_exists($script)) {
            File::ensureDirectoryExists(dirname($script));
            File::copy(__DIR__.'/../../stubs/synapps/resources/js/synapse-rte.js', $script);
            $changed = true;
        }

        $appJs = resource_path('js/app.js');

        if (! file_exists($appJs)) {
            return $changed;
        }

        $content = (string) file_get_contents($appJs);
        $imported = str_contains($content, 'synapse-rte.js');

        if ($enabled === $imported) {
            return $changed;
        }

        if ($enabled) {
            // Directly after the synapps import, so module scripts that extend the editor load after it.
            $anchor = "import '../../synapps/resources/js/synapps-app.js';";
            $content = str_contains($content, $anchor)
                ? str_replace($anchor, $anchor.PHP_EOL.self::APP_JS_IMPORT, $content)
                : rtrim($content).PHP_EOL.self::APP_JS_IMPORT.PHP_EOL;
        } else {
            $content = (string) preg_replace('/^.*synapse-rte\.js.*\R?/m', '', $content);
        }

        file_put_contents($appJs, $content);

        return true;
    }
}
