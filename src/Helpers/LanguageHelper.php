<?php

namespace Upon\Mlang\Helpers;

use Illuminate\Support\Facades\Config;

class LanguageHelper
{
    /**
     * Get all configured languages
     *
     * @return array
     */
    public static function getConfiguredLanguages(): array
    {
        return Config::get('mlang.languages', ['en']);
    }

    /**
     * Get the fallback language
     *
     * @return string
     */
    public static function getFallbackLanguage(): string
    {
        return Config::get('mlang.fallback_language', 'en');
    }

    /**
     * Get the current application locale
     *
     * @return string
     */
    public static function getCurrentLocale(): string
    {
        return app()->getLocale();
    }

    /**
     * Check if a language is configured
     *
     * @param string $locale
     * @return bool
     */
    public static function isLanguageConfigured(string $locale): bool
    {
        return in_array($locale, self::getConfiguredLanguages(), true);
    }

    /**
     * Get locale from Accept-Language header
     *
     * @param string|null $acceptLanguageHeader
     * @return string
     */
    public static function parseAcceptLanguageHeader(?string $acceptLanguageHeader): string
    {
        if (empty($acceptLanguageHeader)) {
            return self::getFallbackLanguage();
        }

        // Parse the Accept-Language header
        $languages = explode(',', $acceptLanguageHeader);
        $preferredLanguages = [];

        foreach ($languages as $language) {
            $parts = explode(';q=', trim($language));
            $code = substr($parts[0], 0, 2); // Get first 2 characters (language code)
            $quality = isset($parts[1]) ? (float) $parts[1] : 1.0;

            $preferredLanguages[$code] = $quality;
        }

        // Sort by quality
        arsort($preferredLanguages);

        // Find first configured language
        $configuredLanguages = self::getConfiguredLanguages();
        foreach (array_keys($preferredLanguages) as $code) {
            if (in_array($code, $configuredLanguages, true)) {
                return $code;
            }
        }

        return self::getFallbackLanguage();
    }

    /**
     * Validate and filter locale
     *
     * @param string|null $locale
     * @return string
     */
    public static function validateAndGetLocale(?string $locale = null): string
    {
        // Use provided locale or current app locale
        $locale = $locale ?? self::getCurrentLocale();

        // Validate locale format
        try {
            SecurityHelper::validateLocale($locale);
        } catch (\InvalidArgumentException $e) {
            return self::getFallbackLanguage();
        }

        // Return locale if configured, otherwise return fallback
        return self::isLanguageConfigured($locale) ? $locale : self::getFallbackLanguage();
    }

    /**
     * Get missing languages for a record
     *
     * @param array $existingLanguages
     * @return array
     */
    public static function getMissingLanguages(array $existingLanguages): array
    {
        $configuredLanguages = self::getConfiguredLanguages();
        return array_diff($configuredLanguages, $existingLanguages);
    }

    /**
     * Get language name from locale code
     *
     * @param string $locale
     * @return string
     */
    public static function getLanguageName(string $locale): string
    {
        $languageNames = [
            'en' => 'English',
            'fr' => 'French',
            'de' => 'German',
            'es' => 'Spanish',
            'it' => 'Italian',
            'pt' => 'Portuguese',
            'nl' => 'Dutch',
            'ru' => 'Russian',
            'zh' => 'Chinese',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'ar' => 'Arabic',
            'hi' => 'Hindi',
            'tr' => 'Turkish',
            'pl' => 'Polish',
            'sv' => 'Swedish',
            'da' => 'Danish',
            'no' => 'Norwegian',
            'fi' => 'Finnish',
        ];

        return $languageNames[$locale] ?? ucfirst($locale);
    }

    /**
     * Sort languages by priority (current locale first, then configured order)
     *
     * @param array $languages
     * @return array
     */
    public static function sortLanguagesByPriority(array $languages): array
    {
        $currentLocale = self::getCurrentLocale();
        $configuredOrder = self::getConfiguredLanguages();

        // Sort by priority
        usort($languages, function ($a, $b) use ($currentLocale, $configuredOrder) {
            // Current locale has highest priority
            if ($a === $currentLocale) {
                return -1;
            }
            if ($b === $currentLocale) {
                return 1;
            }

            // Then by configured order
            $aIndex = array_search($a, $configuredOrder);
            $bIndex = array_search($b, $configuredOrder);

            if ($aIndex === false && $bIndex === false) {
                return 0;
            }
            if ($aIndex === false) {
                return 1;
            }
            if ($bIndex === false) {
                return -1;
            }

            return $aIndex - $bIndex;
        });

        return $languages;
    }

    /**
     * Check if auto-generation is enabled
     *
     * @return bool
     */
    public static function isAutoGenerateEnabled(): bool
    {
        return Config::get('mlang.auto_generate', false);
    }

    /**
     * Check if observer should run during console
     *
     * @return bool
     */
    public static function shouldObserveDuringConsole(): bool
    {
        return Config::get('mlang.observe_during_console', false);
    }

    /**
     * Get all configured models
     *
     * @return array
     */
    public static function getConfiguredModels(): array
    {
        return Config::get('mlang.models', []);
    }
}
