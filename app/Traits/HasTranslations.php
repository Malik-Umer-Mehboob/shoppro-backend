<?php

namespace App\Traits;

use Illuminate\Support\Facades\App;

trait HasTranslations
{
    /**
     * Get the translated value of a field.
     *
     * @param string $field
     * @param string|null $locale
     * @return mixed
     */
    public function getTranslation(string $field, ?string $locale = null)
    {
        $locale = $locale ?: App::getLocale();
        $translations = $this->translations ?? [];

        if (is_string($translations)) {
            $translations = json_decode($translations, true) ?? [];
        }

        // Check if the locale exists in translations
        if (isset($translations[$locale][$field])) {
            return $translations[$locale][$field];
        }

        // Fallback to the main field value if it exists
        return $this->getAttribute($field);
    }

    /**
     * Set the translated value of a field.
     *
     * @param string $field
     * @param string $locale
     * @param mixed $value
     * @return self
     */
    public function setTranslation(string $field, string $locale, $value)
    {
        $translations = $this->translations ?? [];

        if (is_string($translations)) {
            $translations = json_decode($translations, true) ?? [];
        }

        if (!isset($translations[$locale])) {
            $translations[$locale] = [];
        }

        $translations[$locale][$field] = $value;

        $this->translations = $translations;

        return $this;
    }

    /**
     * Get all translations for a specific locale.
     *
     * @param string|null $locale
     * @return array
     */
    public function getTranslations(?string $locale = null): array
    {
        $locale = $locale ?: App::getLocale();
        $translations = $this->translations ?? [];

        if (is_string($translations)) {
            $translations = json_decode($translations, true) ?? [];
        }

        return $translations[$locale] ?? [];
    }
}
