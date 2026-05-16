<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CategoryRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

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
        // Map frontend 'request_type' to backend 'type' if needed
        if (!$request->has('type') && $request->has('request_type')) {
            $request->merge(['type' => $request->request_type]);
        }

        $validated = $request->validate([
            'type' => 'required|in:main,sub,both,access',
            'name' => 'required_if:type,main,sub,both|nullable|string|max:255',
            'category_id' => 'required_if:type,access|nullable|exists:categories,id',
            'subcategory_name' => 'required_if:type,both|nullable|string|max:255',
            'parent_id' => 'required_if:type,sub|nullable|exists:categories,id',
            'description' => 'nullable|string',
            'reason' => 'nullable|string',
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['status'] = 'pending';

        // Ensure empty strings are handled as null for database relations
        foreach (['category_id', 'parent_id', 'subcategory_name', 'description', 'reason'] as $field) {
            if (isset($validated[$field]) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

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
        $requests = CategoryRequest::with(['user:id,name,email', 'parent:id,name', 'category:id,name'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($req) {
                return [
                    'id' => $req->id,
                    'requester_name' => $req->user->name,
                    'requester_email' => $req->user->email,
                    'name' => $req->type === 'access' ? ($req->category->name ?? 'Deleted Category') : $req->name,
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
            $user = User::find($req->user_id);
            if (!$user) {
                throw new \Exception('Requester user not found');
            }

            $categoryToLink = null;
            $type = strtolower($req->type);
            if ($type === 'category') $type = 'main';

            if ($type === 'main' || $type === 'both') {
                // Handle Main Category
                $main = Category::where('name', trim($req->name))
                    ->whereNull('parent_id')
                    ->first();

                if ($main) {
                    $main->update(['is_active' => true]);
                } else {
                    $slug = Str::slug($req->name);
                    $originalSlug = $slug;
                    $count = 1;
                    while (Category::where('slug', $slug)->exists()) {
                        $slug = $originalSlug . '-' . $count++;
                    }

                    $main = Category::create([
                        'name' => trim($req->name),
                        'slug' => $slug,
                        'description' => $req->description,
                        'is_active' => true,
                    ]);
                }

                $user->assignedCategories()->syncWithoutDetaching([$main->id]);
                $categoryToLink = $main->id;

                // Handle Subcategory if both
                if ($type === 'both' && !empty($req->subcategory_name)) {
                    $sub = Category::where('name', trim($req->subcategory_name))
                        ->where('parent_id', $main->id)
                        ->first();
                    
                    if ($sub) {
                        $sub->update(['is_active' => true]);
                    } else {
                        $subSlug = Str::slug($req->subcategory_name);
                        $origSubSlug = $subSlug;
                        $sCount = 1;
                        while (Category::where('slug', $subSlug)->exists()) {
                            $subSlug = $origSubSlug . '-' . $sCount++;
                        }

                        $sub = Category::create([
                            'name' => trim($req->subcategory_name),
                            'slug' => $subSlug,
                            'parent_id' => $main->id,
                            'is_active' => true,
                            'description' => $req->subcategory_description ?? null,
                        ]);
                    }
                    
                    $user->assignedCategories()->syncWithoutDetaching([$sub->id]);
                    $categoryToLink = $sub->id;
                }
            } elseif ($type === 'sub') {
                // Ensure parent exists and is active
                $parent = Category::find($req->parent_id);
                if ($parent) {
                    $curr = $parent;
                    while ($curr) {
                        $curr->update(['is_active' => true]);
                        $curr = $curr->parent;
                    }
                }

                $sub = Category::where('name', trim($req->name))
                    ->where('parent_id', $req->parent_id)
                    ->first();

                if ($sub) {
                    $sub->update(['is_active' => true]);
                } else {
                    $subSlug = Str::slug($req->name);
                    $origSubSlug = $subSlug;
                    $sCount = 1;
                    while (Category::where('slug', $subSlug)->exists()) {
                        $subSlug = $origSubSlug . '-' . $sCount++;
                    }

                    $sub = Category::create([
                        'name' => trim($req->name),
                        'slug' => $subSlug,
                        'parent_id' => $req->parent_id,
                        'is_active' => true,
                        'description' => $req->description,
                    ]);
                }
                
                $user->assignedCategories()->syncWithoutDetaching([$sub->id]);
                $categoryToLink = $sub->id;
            } elseif ($type === 'access') {
                $category = Category::find($req->category_id);
                if ($category) {
                    $curr = $category;
                    while ($curr) {
                        $curr->update(['is_active' => true]);
                        $curr = $curr->parent;
                    }
                    $user->assignedCategories()->syncWithoutDetaching([$category->id]);
                    $categoryToLink = $category->id;
                } else {
                    throw new \Exception('Category not found');
                }
            }

            $req->update([
                'status' => 'approved',
                'category_id' => $categoryToLink
            ]);

            DB::commit();

            // Clear all category related caches
            Cache::forget('categories_all');
            Cache::forget('categories_tree');

            return response()->json([
                'success' => true, 
                'message' => 'Request approved and propagated system-wide.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
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
