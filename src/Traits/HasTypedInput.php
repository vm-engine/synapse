<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Traits;

/**
 * Typed accessors for Artisan command input.
 *
 * `Command::argument()`/`option()` are declared to return
 * `array|bool|float|int|string|null` since Symfony allows array-mode
 * arguments/options. None of the arguments/options these commands read are
 * declared as arrays, so a genuine array here means a caller misused the
 * command — fail loudly instead of silently stringifying (which would fatal
 * anyway, since arrays can't cast to string).
 */
trait HasTypedInput
{
    protected function stringArgument(string $name): string
    {
        return $this->toStringInput($this->argument($name), "argument \"{$name}\"");
    }

    protected function stringOption(string $name): string
    {
        return $this->toStringInput($this->option($name), "option \"{$name}\"");
    }

    private function toStringInput(mixed $value, string $label): string
    {
        if (is_array($value)) {
            throw new \RuntimeException("Expected {$label} to be a single value, got an array.");
        }

        return (string) $value;
    }
}
