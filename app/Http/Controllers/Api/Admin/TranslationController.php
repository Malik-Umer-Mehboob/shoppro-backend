<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Translation;
use App\Models\Language;
use App\Services\TranslationService;
use Illuminate\Http\Request;

class TranslationController extends Controller
{
    protected $translationService;

    public function __construct(TranslationService $translationService)
    {
        $this->translationService = $translationService;
    }

    public function index(Request $request)
    {
        $query = Translation::query();

        if ($request->has('language_id')) {
            $query->where('language_id', $request->language_id);
        }

        if ($request->has('group')) {
            $query->where('group', $request->group);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string',
            'text' => 'required|string',
            'language_id' => 'required|exists:languages,id',
            'group' => 'string',
        ]);

        $translation = $this->translationService->setTranslation(
            $validated['key'],
            $validated['text'],
            $validated['language_id'],
            $validated['group'] ?? 'messages'
        );

        return response()->json($translation, 201);
    }

    public function update(Request $request, Translation $translation)
    {
        $validated = $request->validate([
            'text' => 'required|string',
        ]);

        $translation->update($validated);
        $this->translationService->clearCache($translation->language_id);

        return response()->json($translation);
    }

    public function destroy(Translation $translation)
    {
        $languageId = $translation->language_id;
        $translation->delete();
        $this->translationService->clearCache($languageId);

        return response()->json(null, 204);
    }

    public function getStrings($languageCode)
    {
        $language = Language::where('code', $languageCode)->firstOrFail();
        return response()->json($this->translationService->getTranslationsForLanguage($language->id));
    }
}
