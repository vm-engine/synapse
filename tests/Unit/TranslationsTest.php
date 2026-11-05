<?php

declare(strict_types=1);

/**
 * The package ships lang/id.json because the app locale on Synapse projects is `id`
 * with no English fallback: a key with no entry renders as the key itself. A missing
 * entry is therefore a user-visible defect, and one that is easy to introduce —
 * `aria-label="{{ __('Per page') }}"` looks finished at the call site and reads as
 * English only once an Indonesian screen reader gets to it.
 */
describe('Package translations', function () {
    it('translates every bare key its own views and classes emit', function () {
        $root = dirname(__DIR__, 2);

        /** @var array<string, string> $translations */
        $translations = json_decode((string) file_get_contents($root.'/lang/id.json'), true, 512, JSON_THROW_ON_ERROR);

        // Recursive on purpose: glob()'s `**` matches a single segment, not a tree, so a
        // glob-based scan silently skipped src/Services/**, src/Console/** and the nested
        // component views (notifications, searchable) — half the package.
        $files = [];

        foreach (['/resources/views', '/src'] as $dir) {
            /** @var iterable<SplFileInfo> $iterator */
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.$dir, FilesystemIterator::SKIP_DOTS));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $files[] = $file->getPathname();
                }
            }
        }

        expect(count($files))->toBeGreaterThan(200, 'Far fewer files scanned than the package holds — the scan is no longer reaching them.');

        $missing = [];

        foreach ($files as $file) {
            preg_match_all("/__\('([^']+)'\)/", (string) file_get_contents($file), $matches);

            foreach ($matches[1] as $key) {
                // A namespaced key (`module::group.key`) is the consuming module's to translate.
                if (str_contains($key, '::')) {
                    continue;
                }

                if (! array_key_exists($key, $translations)) {
                    $missing[$key] = str_replace($root.'/', '', $file);
                }
            }
        }

        expect($missing)->toBe([], 'Add these keys to lang/id.json: '.json_encode($missing));
    });
});
