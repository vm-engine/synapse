<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Services;

/**
 * Builds the setup-owned searchable rows: every routed backend nav item
 * (source "nav") and every module.json `searchables` entry (source "module:{id}"),
 * translated once per locale.
 */
class SearchableImporter
{
    /** @var list<string> */
    public array $warnings = [];

    /**
     * @param  list<string>  $locales
     * @return list<array<string, mixed>>
     */
    public function rows(array $locales): array
    {
        $rows = [];

        /** @var array<string, array<string, mixed>> $nav */
        $nav = config('synapps.nav.backend', []);
        $this->walkNav($nav, [], [], null, $locales, $rows);

        /** @var array<string, array<string, mixed>> $modules */
        $modules = config('synapps.modules', []);
        foreach ($modules as $module) {
            foreach ($module['searchables'] ?? [] as $entryKey => $entry) {
                $key = $module['id'].'.'.$entryKey;

                if (empty($entry['name']) || (empty($entry['route']) && empty($entry['url']))) {
                    $this->warnings[] = "Skipped module searchable [{$key}]: needs a name and a route or url.";

                    continue;
                }

                foreach ($locales as $locale) {
                    $description = isset($entry['description']) ? $this->translate($entry['description'], $locale) : null;
                    $rows[] = $this->row('module:'.$module['id'], $key, $locale, $entry, $description, null);
                }
            }
        }

        return $rows;
    }

    /**
     * Depth-first walk of nav.backend. Parent names become the description
     * breadcrumb; an item without its own acl inherits its parent's, since the
     * sidebar hides children of a hidden parent.
     *
     * @param  array<array-key, array<string, mixed>>  $items
     * @param  list<string>  $path
     * @param  list<string>  $parentNames
     * @param  list<string>  $locales
     * @param  list<array<string, mixed>>  $rows
     */
    private function walkNav(array $items, array $path, array $parentNames, ?string $parentAcl, array $locales, array &$rows): void
    {
        foreach ($items as $id => $item) {
            $keyPath = [...$path, (string) $id];
            $acl = $item['acl'] ?? $parentAcl;

            if (! empty($item['route'])) {
                foreach ($locales as $locale) {
                    $description = $parentNames === []
                        ? null
                        : implode(' › ', array_map(fn (string $name) => $this->translate($name, $locale), $parentNames));

                    $rows[] = $this->row('nav', implode('.', $keyPath), $locale, $item, $description, $acl);
                }
            }

            if (! empty($item['sub'])) {
                $this->walkNav($item['sub'], $keyPath, [...$parentNames, $item['name'] ?? (string) $id], $acl, $locales, $rows);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $entry
     * @return array<string, mixed>
     */
    private function row(string $source, string $key, string $locale, array $entry, ?string $description, ?string $inheritedAcl): array
    {
        return [
            'source' => $source,
            'key' => $key,
            'locale' => $locale,
            'title' => $this->translate($entry['name'] ?? $key, $locale),
            'description' => $description,
            'icon' => $entry['icon'] ?? null,
            'route' => $entry['route'] ?? null,
            'route_params' => $entry['route_params'] ?? null,
            'url' => $entry['url'] ?? null,
            'acl' => $entry['acl'] ?? $inheritedAcl,
            'keywords' => isset($entry['keywords']) ? $this->translate($entry['keywords'], $locale) : null,
        ];
    }

    /**
     * Same "__" convention as SyNav::translate(), but for an explicit locale.
     */
    private function translate(string $value, string $locale): string
    {
        return str_starts_with($value, '__') ? (string) __(substr($value, 2), [], $locale) : $value;
    }
}
