<?php

namespace App\Services;

use App\Models\KnowledgeBaseArticle;
use Illuminate\Support\Str;

class KnowledgeBaseService
{
    public function createArticle(array $data)
    {
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }
        return KnowledgeBaseArticle::create($data);
    }

    public function updateArticle($id, array $data)
    {
        $article = KnowledgeBaseArticle::findOrFail($id);
        if (isset($data['title']) && empty($data['slug'])) {
            $data['slug'] = Str::slug($data['title']);
        }
        $article->update($data);
        return $article;
    }

    public function deleteArticle($id)
    {
        $article = KnowledgeBaseArticle::findOrFail($id);
        return $article->delete();
    }

    public function getArticlesByCategory($category)
    {
        return KnowledgeBaseArticle::where('category', $category)->get();
    }

    public function incrementArticleViews($id)
    {
        $article = KnowledgeBaseArticle::findOrFail($id);
        $article->incrementViews();
        return $article;
    }

    public function markArticleAsHelpful($id)
    {
        $article = KnowledgeBaseArticle::findOrFail($id);
        $article->markAsHelpful();
        return $article;
    }

    public function markArticleAsNotHelpful($id)
    {
        $article = KnowledgeBaseArticle::findOrFail($id);
        $article->markAsNotHelpful();
        return $article;
    }

    public function searchArticles($query)
    {
        return KnowledgeBaseArticle::where('title', 'like', "%{$query}%")
            ->orWhere('content', 'like', "%{$query}%")
            ->get();
    }
}
