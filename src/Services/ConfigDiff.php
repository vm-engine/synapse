<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services;

/**
 * Diff/merge helpers for the `synapps/config/*.json` + `*.dev.json` override pairs.
 *
 * Lists (sequential arrays) are treated as single values: compared whole and
 * replaced whole, so a dev override can shorten or empty a base list.
 */
final class ConfigDiff
{
    /**
     * Entries of $after whose value differs from $before, keeping the nesting.
     *
     * @param  array<mixed>  $after
     * @param  array<mixed>  $before
     * @return array<mixed>
     */
    public static function changed(array $after, array $before): array
    {
        $changed = [];

        foreach ($after as $key => $value) {
            if (! array_key_exists($key, $before)) {
                $changed[$key] = $value;

                continue;
            }

            $previous = $before[$key];

            if (is_array($value) && is_array($previous) && self::isMap($value) && self::isMap($previous)) {
                $nested = self::changed($value, $previous);
                if ($nested !== []) {
                    $changed[$key] = $nested;
                }

                continue;
            }

            if ($value !== $previous) {
                $changed[$key] = $value;
            }
        }

        return $changed;
    }

    /**
     * Recursively overlay $override on $base. Maps merge key by key; lists and
     * scalars replace. An empty array over a map is a no-op, because `{}` in a
     * json file decodes to the same `[]` as an empty list.
     *
     * @param  array<mixed>  $base
     * @param  array<mixed>  $override
     * @return array<mixed>
     */
    public static function merge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            $current = $base[$key] ?? null;

            $base[$key] = is_array($current) && self::isMap($current) && is_array($value) && ($value === [] || self::isMap($value))
                ? self::merge($current, $value)
                : $value;
        }

        return $base;
    }

    /**
     * Merge what changed between $before and $after into the given dev json file.
     * The file is left untouched when nothing changed, or when the result cannot be encoded.
     *
     * @param  array<mixed>  $after
     * @param  array<mixed>  $before
     * @return array<mixed> The changed entries that were written.
     *
     * @throws \JsonException
     */
    public static function writeDev(string $devFile, array $after, array $before): array
    {
        $changed = self::changed($after, $before);

        if ($changed === []) {
            return [];
        }

        $existing = file_exists($devFile) ? read_json_file($devFile) : [];

        file_put_contents(
            $devFile,
            json_encode(self::merge($existing, $changed), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)
        );

        return $changed;
    }

    /**
     * Whether the array is keyed (a json object) rather than a list.
     *
     * @param  array<mixed>  $value
     */
    private static function isMap(array $value): bool
    {
        return ! array_is_list($value);
    }
}
