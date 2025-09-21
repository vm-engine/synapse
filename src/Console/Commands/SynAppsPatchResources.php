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
        $jsFile = fopen(resource_path('js/app.js'), 'r+');
        $cssFile = fopen(resource_path('css/app.css'), 'r+');

        $jsLine = "import '" . addslashes(synapps_path('resources' . DS . 'js' . DS . 'synapps.js')) . "';";
        $cssLine = "@import '" . addslashes(synapps_path('resources' . DS . 'css' . DS . 'synapps.css')) . "';";

        $jsPatched = false;
        $jsInsertPos = 0;
        while (!feof($jsFile)) {
            $line = fgets($jsFile);
            if (strpos($line, $jsLine) !== false) {
                $jsPatched = false;
                break;
            }
            if (strpos($line, 'import') !== false) {
                $jsInsertPos = ftell($jsFile);
            }
            $jsPatched = true;
        }
        if ($jsPatched && $jsInsertPos > 0) {
            fseek($jsFile, $jsInsertPos);
            fwrite($jsFile, $jsLine . "\n");
        }
        fclose($jsFile);

        $cssPatched = false;
        $cssInsertPos = 0;
        while (!feof($cssFile)) {
            $line = fgets($cssFile);
            if (strpos($line, $cssLine) !== false) {
                $cssPatched = false;
                break;
            }
            if (strpos($line, '@import') !== false) {
                $cssInsertPos = ftell($cssFile);
            }
            $cssPatched = true;
        }
        if ($cssPatched && $cssInsertPos > 0) {
            fseek($cssFile, $cssInsertPos);
            fwrite($cssFile, $cssLine . "\n");
        }
        fclose($cssFile);

        if ($jsPatched || $cssPatched) {
            $this->info('Resource files patched.');
        }
    }
}
