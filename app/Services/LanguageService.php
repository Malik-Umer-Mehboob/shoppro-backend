<?php

namespace App\Services;

use App\Models\Language;
use Illuminate\Support\Facades\Cache;

class LanguageService
{
    public function getAllLanguages()
    {
        return Cache::remember('all_languages', 3600, function () {
            return Language::all();
        });
    }

    public function getActiveLanguages()
    {
        return Cache::remember('active_languages', 3600, function () {
            return Language::where('is_active', true)->get();
        });
    }

    public function getLanguageByCode($code)
    {
        return Language::where('code', $code)->first();
    }

    public function getDefaultLanguage()
    {
        return Cache::remember('default_language', 3600, function () {
            return Language::where('is_default', true)->first() ?: Language::first();
        });
    }

    public function clearCache()
    {
        Cache::forget('all_languages');
        Cache::forget('active_languages');
        Cache::forget('default_language');
    }
}
