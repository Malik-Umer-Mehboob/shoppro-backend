<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryRequestController extends Controller
{
    /**
     * Display a listing of the seller's own requests.
     */
    public function index(Request $request)
    {
        $requests = CategoryRequest::where('user_id', $request->user()->id)
            ->with('parent:id,name')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    /**
     * Store a new category request from a seller.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:main,sub,both',
            'name' => 'required|string|max:255',
            'subcategory_name' => 'required_if:type,both|nullable|string|max:255',
            'parent_id' => 'required_if:type,sub|nullable|exists:categories,id',
            'description' => 'nullable|string',
            'reason' => 'nullable|string',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = 'pending';

        $categoryRequest = CategoryRequest::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Category request submitted successfully',
            'data' => $categoryRequest
        ]);
    }

    /**
     * Display a listing of all requests (Admin).
     */
    public function adminIndex()
    {
        $requests = CategoryRequest::with(['user:id,name,email', 'parent:id,name'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($req) {
                return [
                    'id' => $req->id,
                    'requester_name' => $req->user->name,
                    'requester_email' => $req->user->email,
                    'name' => $req->name,
                    'type' => $req->type,
                    'status' => $req->status,
                    'description' => $req->description ?? $req->reason,
                    'created_at' => $req->created_at,
                    'rejection_reason' => $req->rejection_reason,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    /**
     * Approve a category request.
     */
    public function approve($id)
    {
        $req = CategoryRequest::findOrFail($id);
        
        if ($req->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Request already processed'], 400);
        }

        DB::beginTransaction();
        try {
            if ($req->type === 'main' || $req->type === 'both') {
                $main = Category::create([
                    'name' => $req->name,
                    'description' => $req->description,
                    'is_active' => true,
                ]);

                if ($req->type === 'both' && $req->subcategory_name) {
                    Category::create([
                        'name' => $req->subcategory_name,
                        'parent_id' => $main->id,
                        'is_active' => true,
                    ]);
                }
            } elseif ($req->type === 'sub') {
                Category::create([
                    'name' => $req->name,
                    'parent_id' => $req->parent_id,
                    'is_active' => true,
                ]);
            }

            $req->update(['status' => 'approved']);
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Request approved and category created']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Failed to create category: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Reject a category request.
     */
    public function reject(Request $request, $id)
    {
        $req = CategoryRequest::findOrFail($id);
        
        if ($req->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'Request already processed'], 400);
        }

        $req->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason
        ]);

        return response()->json(['success' => true, 'message' => 'Request rejected']);
    }
}
