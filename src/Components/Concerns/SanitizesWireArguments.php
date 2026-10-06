<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Components\Concerns;

/**
 * Whitelists values a component interpolates into Livewire expressions
 * (wire:click="method('key')", wire:sort="method"), so a prop can never
 * inject extra JavaScript into the host component's markup.
 */
trait SanitizesWireArguments
{
    /**
     * Return the method name if it is a bare PHP identifier, otherwise null.
     */
    protected function wireMethod(?string $method): ?string
    {
        if ($method === null || preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $method) !== 1) {
            return null;
        }

        return $method;
    }

    /**
     * Return the key as a string if it only contains [A-Za-z0-9_-], otherwise null.
     */
    protected function safeKey(int|string|null $key): ?string
    {
        if ($key === null) {
            return null;
        }

        $key = (string) $key;

        return preg_match('/^[A-Za-z0-9_-]+$/', $key) === 1 ? $key : null;
    }
}
