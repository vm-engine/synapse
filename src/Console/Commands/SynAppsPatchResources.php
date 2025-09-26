<?php

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;

class SynAppsPatchResources extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'synapps:patch-resources';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Patch CSS and JS Resource to add Synapse resource files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $jsLine = "import '" . addslashes(synapps_path('resources' . DS . 'js' . DS . 'synapps.js')) . "';";
        $cssLine = "@import '" . addslashes(synapps_path('resources' . DS . 'css' . DS . 'synapps.css')) . "';";

        $jsPatched = false;
        $jsLines = file(resource_path('js/app.js'));
        $lastImportJs = 0;
        foreach ($jsLines as $row => $line) {
            if (strpos($line, $jsLine) !== false) {
                $jsPatched = false;
                break;
            }
            if (strpos($line, 'import') !== false) {
                $lastImportJs = $row;
            }
            $jsPatched = true;
        }
        if ($jsPatched || empty($jsLines)) {
            array_splice($jsLines, $lastImportJs + 1, 0, $jsLine . "\n");
            file_put_contents(resource_path('js/app.js'), implode("\n", $jsLines));
            $jsPatched = true;
        }

        $cssPatched = false;
        $cssLines = file(resource_path('css/app.css'));
        $lastImportCss = 0;
        foreach ($cssLines as $row => $line) {
            if (strpos($line, $cssLine) !== false) {
                $cssPatched = false;
                break;
            }
            if (strpos($line, '@import') !== false) {
                $lastImportCss = $row;
            }
            $cssPatched = true;
        }
        if ($cssPatched || empty($cssLines)) {
            array_splice($cssLines, $lastImportCss + 1, 0, $cssLine . "\n");
            file_put_contents(resource_path('css/app.css'), implode('', $cssLines));
            $cssPatched = true;
        }

        if ($jsPatched || $cssPatched) {
            $this->components->info('Resource files patched.');
        }
    }
}
