<?php

declare(strict_types=1);

use Symfony\Component\Finder\Finder;

// TranslationsTest only sees __() calls, so literal English in an attribute (a placeholder, an
// aria-label, a title) slipped through and rendered untranslated on Indonesian pages.
it('translates the text it puts in placeholder, aria-label and title attributes', function () {
    $offenders = [];

    foreach (Finder::create()->files()->in(__DIR__.'/../../../resources/views/components')->name('*.blade.php') as $file) {
        $markup = (string) preg_replace('/\{\{--.*?--\}\}/s', '', $file->getContents());

        preg_match_all('/\b(placeholder|aria-label|title)="([A-Za-z][^"{]*)"/', $markup, $matches, PREG_SET_ORDER);

        foreach ($matches as [, $attribute, $text]) {
            $offenders[] = $file->getRelativePathname().": {$attribute}=\"{$text}\"";
        }
    }

    expect($offenders)->toBe([]);
});
