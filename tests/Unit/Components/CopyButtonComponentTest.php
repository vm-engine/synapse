<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use VmEngine\Synapse\Components\CopyButton;

describe('CopyButton Component', function () {
    beforeEach(function () {
        view()->addNamespace('synapse', __DIR__.'/../../../resources/views');
        Blade::component(CopyButton::class, 'synapse-copy-button');
    });

    it('defaults to an icon-only "Copy" button', function () {
        $component = new CopyButton(text: 'abc');

        expect($component->label)->toBe('Copy')
            ->and($component->iconOnly)->toBeTrue();
    });

    it('passes the text and labels to the Alpine component', function () {
        $html = Blade::render('<x-synapse-copy-button text="packages/x" label="Copy path" />');

        expect($html)->toContain('synapseCopyButton(&quot;packages\/x&quot;')
            ->toContain('&quot;label&quot;:&quot;Copy path&quot;')
            ->toContain('&quot;copied&quot;:&quot;Copied&quot;')
            ->toContain('aria-label="Copy path"')
            ->toContain('type="button"')
            ->toContain('aria-live="polite"')
            ->not->toContain('syn-copy-button-text');
    });

    it('renders the visible label when not icon-only', function () {
        $html = Blade::render('<x-synapse-copy-button text="abc" label="Copy token" :icon-only="false" />');

        expect($html)->toContain('syn-copy-button-text')
            ->toContain('Copy token');
    });

    it('merges a caller class instead of duplicating the attribute', function () {
        $html = Blade::render('<x-synapse-copy-button text="abc" class="ml-2" />');

        expect($html)->toContain('class="syn-copy-button ml-2"')
            ->and(substr_count($html, 'class="syn-copy-button'))->toBe(1);
    });

    it('escapes the copied text', function () {
        $html = Blade::render('<x-synapse-copy-button :text="$text" />', ['text' => 'a"b<c']);

        expect($html)->not->toContain('a"b<c')
            ->toContain('&lt;c');
    });
});
