<?php

namespace VmEngine\Synapse\Services\ConfigGenerator\Builder;

use VmEngine\Synapse\Services\ConfigGenerator\BuilderBase;

class System extends BuilderBase
{
    public function init()
    {
        $synappsPath = $this->synapps->synappsPath;

        $this->synapps->config['system'] = json_decode(
            file_get_contents($synappsPath . '/config/system.json'),
            true
        );

        if (file_exists($synappsPath . '/config/system.dev.json')) {
            $tmpEnvSystem = json_decode(
                file_get_contents($synappsPath . '/config/system.dev.json'),
                true
            );
        } else {
            file_put_contents(
                $synappsPath . '/config/system.dev.json',
                json_encode($this->synapps->config['system'], JSON_PRETTY_PRINT)
            );
            $tmpEnvSystem = $this->synapps->config['system'];
        }

        $newEnv = [];

        //set agar hanya load system.dev yang boleh dieditnya saja
        foreach ($this->synapps->mainConfig['allowed_system.dev_key'] as $value) {
            if (isset($tmpEnvSystem[$value])) {
                $newEnv[$value] = $tmpEnvSystem[$value];
            }
        }

        if (count($newEnv) >= 1) {
            file_put_contents(
                $synappsPath . '/config/system.dev.json',
                json_encode($newEnv, JSON_PRETTY_PRINT)
            );
            $this->synapps->config['system'] = recuresive_array_merge(
                $this->synapps->config['system'],
                $newEnv
            );
        }

        $this->initNumberFormat();
    }

    private function initNumberFormat()
    {
        $systemConfig = $this->synapps->config['system'];
        // set default number format
        if (isset($systemConfig['number_format'])) {

            if (!array_key_exists('decimal_limit', $systemConfig['number_format']))
                $systemConfig['number_format']['decimal_limit'] = 0;

            if (!array_key_exists('round_limit', $systemConfig['number_format']))
                $systemConfig['number_format']['round_limit'] = 0;

            if (!isset($systemConfig['number_format']['decimal_symbol']))
                $systemConfig['number_format']['decimal_symbol'] = ',';

            if (!isset($systemConfig['number_format']['thousands_separator_symbol']))
                $systemConfig['number_format']['thousands_separator_symbol'] = '.';
        } else {
            $systemConfig['number_format'] = [
                'decimal_limit' => 0,
                'round_limit' => 0,
                'decimal_symbol' => ',',
                'thousands_separator_symbol' => '.'
            ];
        }

        $this->synapps->config['system'] = $systemConfig;
    }
}
