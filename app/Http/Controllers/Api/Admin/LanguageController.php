<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Language;
use App\Services\LanguageService;
use Illuminate\Http\Request;

class LanguageController extends Controller
{
    protected $languageService;

    public function __construct(LanguageService $languageService)
    {
        $this->languageService = $languageService;
    }

    public function index()
    {
        return response()->json(Language::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'code' => 'required|string|unique:languages',
            'locale' => 'required|string',
            'direction' => 'required|in:ltr,rtl',
            'currency_code' => 'required|string|max:3',
            'currency_symbol' => 'required|string|max:5',
            'exchange_rate' => 'required|numeric',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        if ($validated['is_default'] ?? false) {
            Language::where('is_default', true)->update(['is_default' => false]);
        }

        $language = Language::create($validated);
        $this->languageService->clearCache();

        return response()->json($language, 201);
    }

    public function show(Language $language)
    {
        return response()->json($language);
    }

    public function update(Request $request, Language $language)
    {
        $validated = $request->validate([
            'name' => 'string',
            'code' => 'string|unique:languages,code,' . $language->id,
            'locale' => 'string',
            'direction' => 'in:ltr,rtl',
            'currency_code' => 'string|max:3',
            'currency_symbol' => 'string|max:5',
            'exchange_rate' => 'numeric',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        if ($validated['is_default'] ?? false) {
            Language::where('is_default', true)->update(['is_default' => false]);
        }

        $language->update($validated);
        $this->languageService->clearCache();

        return response()->json($language);
    }

    public function destroy(Language $language)
    {
        if ($language->is_default) {
            return response()->json(['message' => 'Cannot delete default language'], 422);
        }

        $language->delete();
        $this->languageService->clearCache();

        return response()->json(null, 204);
    }
}
