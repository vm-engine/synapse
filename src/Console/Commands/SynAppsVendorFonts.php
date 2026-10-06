<?php

declare(strict_types=1);

namespace VmEngine\Synapse\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SynAppsVendorFonts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'synapps:vendor-fonts {theme? : Theme to vendor fonts for (defaults to the active theme)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Download a theme's Google Fonts and rewrite it to serve them locally (no third-party font CDN)";

    protected const MODERN_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36';

    public function handle(): int
    {
        $theme = $this->argument('theme') ?? config('synapps.apps.themes.active', 'default');
        $themePath = synapps_path("resources/themes/{$theme}");
        $themeJsonPath = "{$themePath}/theme.json";

        if (! File::exists($themeJsonPath)) {
            $this->components->error("Theme '{$theme}' not found: {$themeJsonPath}");

            return 1;
        }

        $themeJson = json_decode(File::get($themeJsonPath), true);

        [$fontsUrl, $affectedLayouts] = $this->findGoogleFontsLink($themeJson);

        if (! $fontsUrl) {
            $this->components->error("No Google Fonts link found in '{$theme}'s theme.json headers.");

            return 1;
        }

        $this->components->info("Fetching Google Fonts CSS from: {$fontsUrl}");

        $cssResponse = Http::withHeaders(['User-Agent' => self::MODERN_USER_AGENT])->get($fontsUrl);

        if (! $cssResponse->successful()) {
            $this->components->error("Failed to fetch Google Fonts CSS (HTTP {$cssResponse->status()}).");

            return 1;
        }

        $css = $cssResponse->body();

        preg_match_all('/url\((https:\/\/fonts\.gstatic\.com\/[^)]+\.woff2)\)/', $css, $matches);
        $fontUrls = array_unique($matches[1]);

        if (empty($fontUrls)) {
            $this->components->error('No woff2 font files found in the fetched Google Fonts CSS.');

            return 1;
        }

        // Download everything into memory first. Nothing is written to disk
        // until every download succeeds, so a failure never leaves the
        // theme's assets or theme.json in a half-updated state.
        $downloaded = [];
        foreach ($fontUrls as $fontUrl) {
            $fontResponse = Http::get($fontUrl);

            if (! $fontResponse->successful()) {
                $this->components->error("Failed to download font file: {$fontUrl} (HTTP {$fontResponse->status()}).");

                return 1;
            }

            $filename = $this->localFilenameFor($fontUrl);
            $downloaded[$filename] = ['url' => $fontUrl, 'body' => $fontResponse->body()];
        }

        $fontsDir = "{$themePath}/assets/fonts";
        File::ensureDirectoryExists($fontsDir);

        $localCss = $css;
        foreach ($downloaded as $filename => $data) {
            File::put("{$fontsDir}/{$filename}", $data['body']);
            $localCss = str_replace($data['url'], "./{$filename}", $localCss);
        }
        File::put("{$fontsDir}/fonts.css", $localCss);

        $localCssPath = "/assets/themes/{$theme}/fonts/fonts.css";

        foreach ($affectedLayouts as $layout) {
            $themeJson['headers'][$layout] = array_values(array_filter(
                $themeJson['headers'][$layout],
                fn ($entry) => ! is_string($entry)
                    || (! str_contains($entry, 'fonts.googleapis.com') && ! str_contains($entry, 'fonts.gstatic.com'))
            ));

            $themeJson['css'][$layout] = $themeJson['css'][$layout] ?? [];
            if (! in_array($localCssPath, $themeJson['css'][$layout], true)) {
                $themeJson['css'][$layout][] = $localCssPath;
            }
        }

        File::put($themeJsonPath, json_encode($themeJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");

        $this->components->success("Fonts vendored for theme '{$theme}'.");
        $this->components->info("Files written to: {$fontsDir}");
        $this->components->warn('Next steps: run `npm run build` to copy the new assets, then `php artisan synapps:config` to recompile.');

        return 0;
    }

    /**
     * Scan every headers.* layout array for a fonts.googleapis.com/css2 link.
     *
     * @param  array<string, mixed>  $themeJson
     * @return array{0: ?string, 1: array<int, string>} [fontsUrl, affectedLayoutKeys]
     */
    protected function findGoogleFontsLink(array $themeJson): array
    {
        $fontsUrl = null;
        $affectedLayouts = [];

        foreach ($themeJson['headers'] ?? [] as $layout => $entries) {
            if (! is_array($entries)) {
                continue;
            }

            foreach ($entries as $entry) {
                if (is_string($entry) && str_contains($entry, 'fonts.googleapis.com/css2')) {
                    if (! $fontsUrl) {
                        preg_match('/href=[\'"]([^\'"]+)[\'"]/', $entry, $m);
                        $fontsUrl = $m[1] ?? null;
                    }
                    $affectedLayouts[] = $layout;
                    break;
                }
            }
        }

        return [$fontsUrl, array_unique($affectedLayouts)];
    }

    /**
     * Derive a stable local filename from a gstatic font URL, e.g.
     * https://fonts.gstatic.com/s/inter/v1/abcdef.woff2 -> inter-3f2a9c1d.woff2
     */
    protected function localFilenameFor(string $fontUrl): string
    {
        $familySlug = 'font';
        if (preg_match('#/s/([a-z0-9]+)/#', $fontUrl, $m)) {
            $familySlug = Str::slug($m[1]);
        }

        $hash = substr(md5($fontUrl), 0, 8);

        return "{$familySlug}-{$hash}.woff2";
    }
}
