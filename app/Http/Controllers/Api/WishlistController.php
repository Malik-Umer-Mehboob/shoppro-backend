<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class WishlistController extends Controller
{
    public function show(Request $request)
    {
        $wishlist = Wishlist::firstOrCreate(['user_id' => auth('sanctum')->id()]);
        return response()->json($wishlist->load('items.product.category'));
    }

    public function showPublic($token)
    {
        $wishlist = Wishlist::where('share_token', $token)
            ->where('privacy', 'public')
            ->firstOrFail();
            
        return response()->json($wishlist->load('items.product.category'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
        ]);

        $wishlist = Wishlist::firstOrCreate(['user_id' => auth('sanctum')->id()]);

        if (!$wishlist->share_token) {
            $wishlist->update(['share_token' => Str::random(32)]);
        }

        $exists = $wishlist->items()->where('product_id', $request->product_id)->exists();

        if (!$exists) {
            $wishlist->items()->create([
                'product_id' => $request->product_id,
            ]);
            return response()->json(['message' => 'Product added to wishlist']);
        }

        return response()->json(['message' => 'Product already in wishlist'], 200);
    }

    public function updatePrivacy(Request $request)
    {
        $request->validate([
            'privacy' => 'required|in:public,private',
        ]);

        $wishlist = Wishlist::where('user_id', auth('sanctum')->id())->firstOrFail();
        
        if ($request->privacy === 'public' && !$wishlist->share_token) {
            $wishlist->share_token = Str::random(32);
        }
        
        $wishlist->privacy = $request->privacy;
        $wishlist->save();

        return response()->json($wishlist);
    }

    public function destroy($itemId)
    {
        $wishlist = Wishlist::where('user_id', auth('sanctum')->id())->first();
        if (!$wishlist) return response()->json(['message' => 'Wishlist not found'], 404);

        $item = $wishlist->items()->findOrFail($itemId);
        $item->delete();

        return response()->json(['message' => 'Product removed from wishlist']);
    }
}
