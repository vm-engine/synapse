<?php

namespace VmEngine\Synapse\Services;

use Illuminate\Database\Eloquent\Collection;
use VmEngine\Synapse\Models\Language;

class DbLang
{
    private array $cache = [];

    private string $currentLanguage = 'en';

    private bool $multiLanguageEnabled = false;

    private array $enabledLanguages = [];

    public function __construct(?string $languageCode = null)
    {
        $this->currentLanguage = $languageCode ?? config('app.locale', 'en');
        $this->multiLanguageEnabled = config('synapps.apps.multi_language', false);
        $this->enabledLanguages = config('synapps.apps.enabled_languages', ['en']);

        $this->loadLanguage($this->currentLanguage);
    }

    public function setLanguage(string $languageCode): void
    {
        if ($this->currentLanguage !== $languageCode) {
            $this->currentLanguage = $languageCode;
            $this->cache = [];
            $this->loadLanguage($languageCode);
        }
    }

    public function getLanguage(): string
    {
        return $this->currentLanguage;
    }

    public function get($key, ?string $languageCode = null)
    {
        $lang = $languageCode ?? $this->currentLanguage;

        // If requesting different language, fetch directly
        if ($lang !== $this->currentLanguage) {
            $translation = Language::where('language_code', $lang)
                ->where('key', $key)
                ->first();

            return $translation->value ?? $key;
        }

        return $this->cache[$key] ?? $key;
    }

    public function set($key, $value, $label = null, ?string $languageCode = null)
    {
        $lang = $languageCode ?? $this->currentLanguage;

        $data = ['value' => $value];

        if ($label !== null) {
            $data['label'] = $label;
        }

        Language::updateOrCreate(
            ['language_code' => $lang, 'key' => $key],
            $data
        );

        // If multi-language is enabled and no specific language was provided,
        // auto-create empty entries for all enabled languages
        if ($this->multiLanguageEnabled && $languageCode === null) {
            foreach ($this->enabledLanguages as $enabledLang) {
                if ($enabledLang !== $lang) {
                    // Check if translation already exists for this language
                    $exists = Language::where('language_code', $enabledLang)
                        ->where('key', $key)
                        ->exists();

                    if (! $exists) {
                        // Create with empty value (key will be returned as fallback)
                        Language::create([
                            'language_code' => $enabledLang,
                            'key' => $key,
                            'value' => null,
                            'label' => $label,
                        ]);
                    }
                }
            }
        }

        // Update cache if it's for current language
        if ($lang === $this->currentLanguage) {
            $this->cache[$key] = $value;
        }

        return $value;
    }

    private function loadLanguage(string $languageCode): void
    {
        /** @var Collection<int, Language> $langs */
        $langs = Language::where('language_code', $languageCode)->get();

        foreach ($langs as $lang) {
            $this->cache[$lang->key] = $lang->value;
        }
    }
}
