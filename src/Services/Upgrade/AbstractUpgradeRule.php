<?php

namespace VmEngine\Synapse\Services\Upgrade;

use LogicException;

abstract class AbstractUpgradeRule implements UpgradeRule
{
    public function fix(string $contents): string
    {
        throw new LogicException(sprintf('Rule "%s" is not auto-fixable.', $this->id()));
    }
}
