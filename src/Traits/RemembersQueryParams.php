<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Traits;

use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * Persists declared Livewire properties to session so list pages
 * automatically restore filters, search, and pagination when the
 * user navigates back via breadcrumbs or browser history.
 *
 * URL query params always take precedence over session values,
 * so bookmarked/shared URLs work as expected.
 *
 * @phpstan-ignore trait.unused
 */
trait RemembersQueryParams
{
    /**
     * Properties to persist in session.
     * Override in component to declare which properties to remember.
     *
     * @return array<int, string>
     */
    protected function rememberedParams(): array
    {
        return [];
    }

    /**
     * Session key for storing remembered params.
     * Override for a custom key.
     */
    protected function rememberKey(): string
    {
        return 'synapse:query_params:'.static::class;
    }

    /**
     * Restore remembered values from session when URL has no query params.
     * Called automatically by Livewire's mount lifecycle.
     */
    public function mountRemembersQueryParams(): void
    {
        $params = $this->rememberedParams();

        if ($params === []) {
            return;
        }

        $stored = session($this->rememberKey(), []);

        if (! is_array($stored) || $stored === []) {
            return;
        }

        $urlParams = $this->resolveCurrentUrlParams($params);
        $restored = [];

        foreach ($params as $param) {
            if (! property_exists($this, $param)) {
                continue;
            }

            if (in_array($param, $urlParams, true)) {
                continue;
            }

            if (array_key_exists($param, $stored)) {
                if ($stored[$param] !== $this->{$param}) {
                    $restored[$param] = $stored[$param];
                }

                $this->{$param} = $stored[$param];
            }
        }

        // Livewire's #[Url] tracking takes the restored value as its baseline and never
        // writes it to the address bar, so the page would show filtered/trashed results
        // under a bare URL. synapse-components.js merges this into the URL (replaceState).
        $query = $this->restoredQuery($restored);

        if ($query !== [] && $this instanceof Component) {
            $this->dispatch('synapse-restored-query', query: $query);
        }
    }

    /**
     * Flat query-string pairs for the restored values: #[Url] aliases as keys, nulls
     * dropped, arrays flattened to key[i] / key[a][b] (the form Livewire reads back as
     * arrays — a plain "1,2" would hydrate an array property as a string).
     *
     * @param  array<string, mixed>  $restored
     * @return array<string, scalar>
     */
    protected function restoredQuery(array $restored): array
    {
        $query = [];

        foreach ($restored as $param => $value) {
            $this->flattenQueryValue($query, $this->queryStringName($param), $value);
        }

        return $query;
    }

    /**
     * @param  array<string, scalar>  $query
     */
    private function flattenQueryValue(array &$query, string $key, mixed $value): void
    {
        if ($value === null) {
            return;
        }

        if (is_array($value)) {
            foreach ($value as $childKey => $childValue) {
                $this->flattenQueryValue($query, "{$key}[{$childKey}]", $childValue);
            }

            return;
        }

        if (! is_scalar($value)) {
            return;
        }

        // Livewire writes booleans to the URL as true/false and reads them back that way.
        $query[$key] = is_bool($value) ? ($value ? 'true' : 'false') : $value;
    }

    /**
     * The query-string key for a property: its #[Url(as: ...)] alias, else its name.
     */
    protected function queryStringName(string $param): string
    {
        if (! property_exists($this, $param)) {
            return $param;
        }

        foreach ((new \ReflectionProperty($this, $param))->getAttributes(Url::class) as $attribute) {
            $alias = $attribute->getArguments()['as'] ?? null;

            if (is_string($alias) && $alias !== '') {
                return $alias;
            }
        }

        return $param;
    }

    /**
     * Save current values to session on every Livewire response.
     * Called automatically by Livewire's dehydrate lifecycle.
     */
    public function dehydrateRemembersQueryParams(): void
    {
        $params = $this->rememberedParams();

        if ($params === []) {
            return;
        }

        $values = [];
        foreach ($params as $param) {
            if (property_exists($this, $param)) {
                $values[$param] = $this->{$param};
            }
        }

        session([$this->rememberKey() => $values]);
    }

    /**
     * Clear remembered params from session.
     * Call this from "reset filters" or "clear search" actions.
     */
    public function forgetRememberedParams(): void
    {
        session()->forget($this->rememberKey());
    }

    /**
     * Read stored param values without modifying properties.
     *
     * @return array<string, mixed>
     */
    public function getRememberedParamValues(): array
    {
        $stored = session($this->rememberKey(), []);

        return is_array($stored) ? $stored : [];
    }

    /**
     * Determine which remembered params are present in the current URL query string.
     *
     * Checks the initial request URL first, then falls back to the Referer header
     * for Livewire AJAX requests.
     *
     * @param  array<int, string>  $params
     * @return array<int, string>
     */
    protected function resolveCurrentUrlParams(array $params): array
    {
        $queryString = request()->server('QUERY_STRING', '');

        if ($queryString === '' || $queryString === null) {
            $referer = request()->header('Referer', '');
            $parsed = is_string($referer) ? parse_url($referer) : [];
            $queryString = $parsed['query'] ?? '';
        }

        if ($queryString === '') {
            return [];
        }

        parse_str($queryString, $parsed);

        $found = [];
        foreach ($params as $param) {
            if (array_key_exists($this->queryStringName($param), $parsed)) {
                $found[] = $param;
            }
        }

        return $found;
    }
}
