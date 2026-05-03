<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Helpers\NotificationHelper;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    // Get all categories with subcategories
    public function index()
    {
        $categories = Category::with('children')
            ->whereNull('parent_id')
            ->orderBy('order')
            ->get()
            ->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                    'description' => $cat->description,
                    'image' => $cat->image
                        ? asset('storage/' . $cat->image)
                        : null,
                    'is_active' => $cat->is_active,
                    'order' => $cat->order,
                    'products_count' => $cat->products()->count(),
                    'children' => $cat->children->map(function ($child) {
                        return [
                            'id' => $child->id,
                            'name' => $child->name,
                            'slug' => $child->slug,
                            'description' => $child->description,
                            'image' => $child->image
                                ? asset('storage/' . $child->image)
                                : null,
                            'is_active' => $child->is_active,
                            'order' => $child->order,
                            'products_count' => $child->products()->count(),
                            'parent_id' => $child->parent_id,
                        ];
                    }),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    // Create category or subcategory
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'order' => 'nullable|integer',
        ]);

        $slug = Str::slug($request->name);
        $originalSlug = $slug;
        $count = 1;

        while (Category::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        $category = Category::create([
            'name' => $request->name,
            'slug' => $slug,
            'parent_id' => $request->parent_id ?? null,
            'description' => $request->description ?? null,
            'is_active' => $request->is_active ?? true,
            'order' => $request->order ?? 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => $request->parent_id
                ? 'Subcategory created successfully'
                : 'Category created successfully',
            'data' => $category,
        ]);
    }

    // Update category
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'order' => 'nullable|integer',
        ]);

        // Regenerate slug if name changed
        if ($request->name !== $category->name) {
            $slug = Str::slug($request->name);
            $originalSlug = $slug;
            $count = 1;
            while (Category::where('slug', $slug)
                ->where('id', '!=', $id)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }
            $category->slug = $slug;
        }

        $category->update([
            'name' => $request->name,
            'slug' => $category->slug,
            'description' => $request->description,
            'is_active' => $request->is_active ?? $category->is_active,
            'order' => $request->order ?? $category->order,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category,
        ]);
    }

    // Delete category
    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        // Check if has products directly
        if ($category->products()->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete category that has products. '
                    . 'Move or delete products first.',
            ], 422);
        }

        // If main category, check subcategories for products
        if (is_null($category->parent_id)) {
            $children = Category::where(
                'parent_id', $category->id
            )->get();

            foreach ($children as $child) {
                if ($child->products()->count() > 0) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot delete. Subcategory "'
                            . $child->name
                            . '" has products. Move them first.',
                    ], 422);
                }
            }

            // Delete all subcategories first
            Category::where('parent_id', $category->id)->delete();
        }

        // Delete the category itself
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => is_null($category->parent_id)
                ? 'Category and all subcategories deleted!'
                : 'Subcategory deleted successfully!',
        ]);
    }

    // Toggle active status
    public function toggleActive($id)
    {
        $category = Category::findOrFail($id);
        $category->update(['is_active' => !$category->is_active]);

        return response()->json([
            'success' => true,
            'message' => $category->is_active
                ? 'Category activated'
                : 'Category deactivated',
        ]);
    }

    // Get category requests from sellers
    public function requests()
    {
        $requests = \DB::table('category_requests')
            ->join('users', 'users.id', '=',
                'category_requests.user_id')
            ->select(
                'category_requests.*',
                'users.name as requester_name',
                'users.email as requester_email'
            )
            ->orderBy('category_requests.created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    // Approve category request
    public function approveRequest($id)
    {
        $req = \DB::table('category_requests')
            ->where('id', $id)->first();

        if (!$req) {
            return response()->json([
                'success' => false,
                'message' => 'Request not found',
            ], 404);
        }

        $slug = Str::slug($req->name);
        $originalSlug = $slug;
        $count = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        // Create main category (or sub for type='sub')
        $mainCategory = Category::create([
            'name'        => $req->name,
            'slug'        => $slug,
            'parent_id'   => $req->parent_id ?? null,
            'description' => $req->description,
            'is_active'   => true,
            'order'       => 0,
        ]);

        // If request type is 'both', create subcategory under the new main category
        if ($req->type === 'both' && !empty($req->subcategory_name)) {
            $subSlug = Str::slug($req->subcategory_name);
            $originalSubSlug = $subSlug;
            $subCount = 1;
            while (Category::where('slug', $subSlug)->exists()) {
                $subSlug = $originalSubSlug . '-' . $subCount++;
            }

            Category::create([
                'name'        => $req->subcategory_name,
                'slug'        => $subSlug,
                'parent_id'   => $mainCategory->id,
                'description' => $req->subcategory_description ?? null,
                'is_active'   => true,
                'order'       => 0,
            ]);
        }

        \DB::table('category_requests')
            ->where('id', $id)
            ->update([
                'status'     => 'approved',
                'updated_at' => now(),
            ]);

        $requester = \App\Models\User::find($req->user_id);
        $requesterRole = $requester?->getRoleNames()->first() ?? 'seller';

        NotificationHelper::send(
            $req->user_id,
            'category.approved',
            'Category Request Approved! ✅',
            "Your category '{$req->name}' has been approved and is now live!",
            ['url' => $requesterRole === 'support'
                ? '/support/dashboard'
                : '/seller/category-request']
        );

        return response()->json([
            'success' => true,
            'message' => $req->type === 'both'
                ? 'Category and subcategory both created!'
                : 'Category created successfully!',
            'data'    => $mainCategory,
        ]);
    }

    // Reject category request
    public function rejectRequest(Request $request, $id)
    {
        $req = \DB::table('category_requests')->where('id', $id)->first();

        \DB::table('category_requests')
            ->where('id', $id)
            ->update([
                'status' => 'rejected',
                'rejection_reason' => $request->reason ?? null,
                'updated_at' => now(),
            ]);

        if ($req) {
            NotificationHelper::send(
                $req->user_id,
                'category.rejected',
                'Category Request Update ❌',
                "Your category request '{$req->name}' was not approved."
                    . ($request->reason ? " Reason: {$request->reason}" : ''),
                ['url' => '/seller/category-request']
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Category request rejected',
        ]);
    }
}
