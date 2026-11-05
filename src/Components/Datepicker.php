<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components;

use Illuminate\View\Component;

class Datepicker extends Component
{
    /**
     * The trailing x-data argument, pre-rendered.
     *
     * Empty for the default date mode so existing markup renders byte-for-byte as before;
     * only a month-mode picker carries the extra argument.
     */
    public string $modeArgument;

    /**
     * @param  string  $flatpickr  JSON-encoded flatpickr options, interpolated into x-data.
     * @param  string  $mode  'date' (default) or 'month' — a year/month grid via flatpickr's
     *                        monthSelect plugin, submitting 'Y-m' like <input type="month">.
     */
    public function __construct(
        public string $flatpickr = '{}',
        public string $mode = 'date',
    ) {
        // Whitelisted, not escaped: $mode is interpolated into a JS string literal inside an
        // x-data attribute, so no call-site value may reach it verbatim.
        $this->mode = $mode === 'month' ? 'month' : 'date';
        $this->modeArgument = $this->mode === 'month' ? ", 'month'" : '';
    }

    public function render()
    {
        return view('synapse::components.datepicker');
    }
}
