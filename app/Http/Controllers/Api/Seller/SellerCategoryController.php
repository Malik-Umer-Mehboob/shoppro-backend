<?php
namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Helpers\NotificationHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SellerCategoryController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'request_type' => 'required|in:main,sub,both',
            'name'         => 'required|string|max:255',
            'subcategory_name' => 'nullable|string|max:255',
            'parent_id'    => 'nullable|exists:categories,id',
            'description'  => 'nullable|string',
            'reason'       => 'nullable|string',
        ]);

        $user = $request->user();

        $id = DB::table('category_requests')->insertGetId([
            'user_id'              => $user->id,
            'name'                 => $request->name,
            'subcategory_name'     => $request->subcategory_name,
            'type'                 => $request->request_type,
            'parent_id'            => $request->parent_id,
            'description'          => $request->description,
            'reason'               => $request->reason ?? null,
            'status'               => 'pending',
            'created_at'           => now(),
            'updated_at'           => now(),
        ]);

        NotificationHelper::sendToRole(
            'admin',
            'category.request',
            'New Category Request 📁',
            "{$user->name} requested new category: '{$request->name}'",
            ['url' => '/admin/categories']
        );

        return response()->json([
            'success' => true,
            'message' => 'Request sent to admin!',
            'data'    => ['id' => $id],
        ]);
    }

    public function myRequests(Request $request)
    {
        $user = $request->user();

        $requests = DB::table('category_requests')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $requests,
        ]);
    }
}
