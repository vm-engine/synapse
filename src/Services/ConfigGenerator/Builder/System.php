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

        if (!file_exists($synappsPath . '/config/system.dev.json')) {
            file_put_contents(
                $synappsPath . '/config/system.dev.json',
                json_encode($this->synapps->config['system'], JSON_PRETTY_PRINT)
            );
        }

        $tmpEnvSystem = json_decode(
            file_get_contents($synappsPath . '/config/system.dev.json'),
            true
        );

        $newEnv = [];

        foreach ($this->synapps->mainConfig['allowed_dev_keys'] as $value) {
            if (isset($tmpEnvSystem[$value])) {
                $newEnv[$value] = $tmpEnvSystem[$value];
            }
        }

        if (count($newEnv) >= 1) {
            file_put_contents(
                $synappsPath . '/config/system.dev.json',
                json_encode($newEnv, JSON_PRETTY_PRINT)
            );
            $this->synapps->config['system'] = array_replace_recursive(
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
        $systemConfig['number_format'] = [
            'decimal_limit' => 0,
            'round_limit' => 0,
            'decimal_symbol' => ',',
            'thousands_separator_symbol' => '.'
        ];
        if (isset($systemConfig['number_format'])) {

            if (!array_key_exists('decimal_limit', $systemConfig['number_format'])) {
                $systemConfig['number_format']['decimal_limit'] = 0;
            }

            if (!array_key_exists('round_limit', $systemConfig['number_format'])) {
                $systemConfig['number_format']['round_limit'] = 0;
            }

            if (!isset($systemConfig['number_format']['decimal_symbol'])) {
                $systemConfig['number_format']['decimal_symbol'] = ',';
            }

            if (!isset($systemConfig['number_format']['thousands_separator_symbol'])) {
                $systemConfig['number_format']['thousands_separator_symbol'] = '.';
            }
        }

        $this->synapps->config['system'] = $systemConfig;
    }

    public function finalize()
    {
        file_put_contents(
            $this->synapps->synappsPath . '/config/compiled/checksum',
            $this->synapps->configHash
        );

        file_put_contents(
            $this->synapps->synappsPath . '/config/compiled/system.json',
            json_encode($this->synapps->config['system'], JSON_PRETTY_PRINT)
        );
    }
}
