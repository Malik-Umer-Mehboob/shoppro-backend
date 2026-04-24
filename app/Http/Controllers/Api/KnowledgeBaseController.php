<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\KnowledgeBaseService;
use Illuminate\Http\Request;

class KnowledgeBaseController extends Controller
{
    protected $kbService;

    public function __construct(KnowledgeBaseService $kbService)
    {
        $this->kbService = $kbService;
    }

    public function index(Request $request)
    {
        if ($request->has('category')) {
            $articles = $this->kbService->getArticlesByCategory($request->category);
        } elseif ($request->has('q')) {
            $articles = $this->kbService->searchArticles($request->q);
        } else {
            $articles = \App\Models\KnowledgeBaseArticle::all();
        }

        return response()->json(['success' => true, 'data' => $articles]);
    }

    public function show($slug)
    {
        $article = \App\Models\KnowledgeBaseArticle::where('slug', $slug)->firstOrFail();
        $this->kbService->incrementArticleViews($article->id);

        return response()->json(['success' => true, 'data' => $article]);
    }

    public function vote($id, Request $request)
    {
        $request->validate(['type' => 'required|string|in:helpful,not_helpful']);

        if ($request->type === 'helpful') {
            $article = $this->kbService->markArticleAsHelpful($id);
        } else {
            $article = $this->kbService->markArticleAsNotHelpful($id);
        }

        return response()->json(['success' => true, 'data' => $article]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'    => 'required|string|max:255',
            'category' => 'required|string',
            'content'  => 'required|string',
        ]);

        $article = $this->kbService->createArticle($request->all());

        return response()->json(['success' => true, 'data' => $article], 201);
    }

    public function update($id, Request $request)
    {
        $article = $this->kbService->updateArticle($id, $request->all());
        return response()->json(['success' => true, 'data' => $article]);
    }

    public function destroy($id)
    {
        $this->kbService->deleteArticle($id);
        return response()->json(['success' => true, 'message' => 'Article deleted']);
    }
}
