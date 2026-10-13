<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Rte;

/**
 * Server-side HTML allowlist for <x-synapse-rte> content (HTMLPurifier via mews/purifier).
 * Keeps basic formatting, lists, links and images; drops scripts, event handlers, inline
 * styles and non-http(s)/mailto URLs. A module that needs more (media, alignment) keeps
 * its own purifier profile.
 */
final class RichText
{
    /** @var array<string, mixed> */
    private const CONFIG = [
        'HTML.Allowed' => 'p,br,strong,b,em,i,u,s,h2,h3,h4,ul,ol,li,blockquote,code,pre,a[href|title|target],img[src|alt|width|height]',
        'URI.AllowedSchemes' => ['http' => true, 'https' => true, 'mailto' => true],
        'Attr.AllowedFrameTargets' => ['_blank'],
        'AutoFormat.RemoveEmpty' => true,
        'AutoFormat.RemoveEmpty.RemoveNbsp' => true,
    ];

    /**
     * The cleaned HTML, or null when nothing visible is left. Without mews/purifier (a host
     * that never enabled the editor) no HTML can be kept safely, so the input is stored as
     * escaped plain text.
     */
    public static function clean(?string $html): ?string
    {
        if (! function_exists('clean')) {
            return self::plain((string) $html);
        }

        $clean = trim((string) clean((string) $html, self::CONFIG));

        return trim(strip_tags($clean)) === '' && ! str_contains($clean, '<img') ? null : $clean;
    }

    /** What was typed, as safe plain text: tags escaped, line breaks kept. Null when blank. */
    public static function plain(string $text): ?string
    {
        $text = trim($text);

        return $text === '' ? null : nl2br(e($text));
    }
}
