<?php

namespace App\Services;

use App\Models\Translation;
use Illuminate\Support\Facades\Cache;

class TranslationService
{
    public function getTranslationsForLanguage($languageId)
    {
        return Cache::remember("translations_{$languageId}", 3600, function () use ($languageId) {
            return Translation::where('language_id', $languageId)
                ->get()
                ->groupBy('group')
                ->map(function ($items) {
                    return $items->pluck('text', 'key');
                });
        });
    }

    public function setTranslation($key, $text, $languageId, $group = 'messages')
    {
        $translation = Translation::updateOrCreate(
            ['key' => $key, 'language_id' => $languageId, 'group' => $group],
            ['text' => $text]
        );

        Cache::forget("translations_{$languageId}");

        return $translation;
    }

    public function clearCache($languageId)
    {
        Cache::forget("translations_{$languageId}");
    }
}
