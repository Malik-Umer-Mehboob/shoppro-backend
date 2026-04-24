<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Question;
use Illuminate\Http\Request;

class QuestionController extends Controller
{
    /**
     * GET /api/products/{productId}/questions
     */
    public function index($productId)
    {
        $questions = Question::where('product_id', $productId)
            ->with(['user:id,name', 'answeredByUser:id,name'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json($questions);
    }

    /**
     * POST /api/products/{productId}/questions
     */
    public function store(Request $request, $productId)
    {
        $request->validate([
            'question' => 'required|string|min:10|max:1000',
        ]);

        $question = Question::create([
            'product_id' => $productId,
            'user_id'    => $request->user()->id,
            'question'   => $request->input('question'),
        ]);

        return response()->json([
            'message'  => 'Question submitted successfully!',
            'question' => $question->load('user:id,name'),
        ], 201);
    }

    /**
     * POST /api/questions/{id}/answer
     */
    public function answer(Request $request, $id)
    {
        $question = Question::findOrFail($id);

        // Only admin or the question author can answer
        $user = $request->user();
        if (!$user->isAdmin() && $question->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        if ($question->isAnswered()) {
            return response()->json(['message' => 'This question has already been answered.'], 422);
        }

        $request->validate([
            'answer' => 'required|string|min:5|max:2000',
        ]);

        $question->update([
            'answer'      => $request->input('answer'),
            'answered_by' => $user->id,
            'answered_at' => now(),
        ]);

        return response()->json([
            'message'  => 'Answer posted successfully!',
            'question' => $question->fresh()->load(['user:id,name', 'answeredByUser:id,name']),
        ]);
    }

    /**
     * DELETE /api/questions/{id}
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        $question = Question::findOrFail($id);

        if ($question->user_id !== $user->id && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $question->delete();
        return response()->json(['message' => 'Question deleted.']);
    }
}
