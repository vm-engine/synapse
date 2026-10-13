<?php

declare(strict_types=1);

use VmEngine\Synapse\Rte\RichText;

it('keeps basic formatting, links and images', function () {
    $html = '<h2>Title</h2><p><strong>Bold</strong> <a href="https://example.test" target="_blank">link</a></p><ul><li>One</li></ul><img src="https://example.test/a.png" alt="A">';

    expect(RichText::clean($html))->toContain('<h2>Title</h2>')
        ->toContain('<strong>Bold</strong>')
        ->toContain('href="https://example.test"')
        ->toContain('<li>One</li>')
        ->toContain('src="https://example.test/a.png"');
});

it('drops scripts, event handlers, inline styles and unsafe URLs', function () {
    $clean = (string) RichText::clean('<p onclick="x()" style="color:red">Hi<script>alert(1)</script></p><a href="javascript:alert(1)">bad</a><iframe src="https://evil.test"></iframe>');

    expect($clean)->toContain('Hi')
        ->not->toContain('script')
        ->not->toContain('onclick')
        ->not->toContain('style=')
        ->not->toContain('javascript:')
        ->not->toContain('iframe');
});

it('stores escaped plain text when the purifier package is not installed', function () {
    expect(RichText::plain("<b>Hi</b> & bye\nsecond <script>x</script>"))
        ->toBe("&lt;b&gt;Hi&lt;/b&gt; &amp; bye<br />\nsecond &lt;script&gt;x&lt;/script&gt;")
        ->and(RichText::plain("  \n "))->toBeNull();
});

it('returns null when nothing visible is left', function () {
    expect(RichText::clean('<p><br></p>'))->toBeNull()
        ->and(RichText::clean('   '))->toBeNull()
        ->and(RichText::clean(null))->toBeNull()
        ->and(RichText::clean('<img src="https://example.test/a.png" alt="">'))->not->toBeNull();
});
