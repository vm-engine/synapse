<?php

namespace VmEngine\Synapse\Traits;

use Livewire\Attributes\Url;

/**
 * @phpstan-ignore trait.unused
 */
trait WithReturnUrl
{
    #[Url(as: 'back')]
    public string $backUrl = '';

    protected function redirectBack(string $defaultRoute, array $parameters = []): void
    {
        if ($this->backUrl && $this->isSafeReturnUrl($this->backUrl)) {
            $this->redirect($this->backUrl, navigate: true);

            return;
        }

        $this->redirectRoute($defaultRoute, $parameters, navigate: true);
    }

    private function isSafeReturnUrl(string $url): bool
    {
        $parsed = parse_url($url);
        $appParsed = parse_url(url('/'));

        return ($parsed['scheme'] ?? '') === ($appParsed['scheme'] ?? '')
            && ($parsed['host'] ?? '') === ($appParsed['host'] ?? '');
    }
}
