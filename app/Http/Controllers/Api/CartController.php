<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CartController extends Controller
{
    private function getCart(Request $request)
    {
        $sessionId = $request->header('X-Session-ID') ?? $request->input('session_id');

        if (auth('sanctum')->check()) {
            $user = auth('sanctum')->user();
            $userCart = Cart::firstOrCreate(['user_id' => $user->id], [
                'session_id' => $sessionId ?? (string) Str::uuid(),
            ]);

            // If there's a guest cart with this session ID, merge it and delete it
            if ($sessionId) {
                $guestCart = Cart::where('session_id', $sessionId)
                    ->whereNull('user_id')
                    ->first();

                if ($guestCart) {
                    foreach ($guestCart->items as $item) {
                        // Check if item already exists in user cart
                        $existingItem = $userCart->items()
                            ->where('product_id', $item->product_id)
                            ->where('variant_id', $item->variant_id)
                            ->first();

                        if ($existingItem) {
                            $existingItem->quantity += $item->quantity;
                            $existingItem->save();
                            $item->delete();
                        } else {
                            $item->cart_id = $userCart->id;
                            $item->save();
                        }
                    }
                    $guestCart->delete();
                    $userCart->updateTotals();
                }
            }
            
            return $userCart;
        }

        if (!$sessionId) {
            return null;
        }

        return Cart::firstOrCreate(['session_id' => $sessionId], [
            'status' => 'new'
        ]);
    }

    public function show(Request $request)
    {
        $cart = $this->getCart($request);

        if (!$cart) {
            return response()->json(['message' => 'Cart not found', 'items' => []], 200);
        }

        return response()->json($cart->load(['items.product', 'items.variant']));
    }

    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
            'session_id' => 'nullable|string',
        ]);

        $cart = $this->getCart($request);
        
        if (!$cart) {
            $cart = Cart::create([
                'session_id' => $request->input('session_id') ?? (string) Str::uuid(),
                'user_id' => auth('sanctum')->id(),
            ]);
        }

        $product = Product::findOrFail($request->product_id);
        $price = $product->sale_price ?? $product->price;

        if ($request->variant_id) {
            $variant = ProductVariant::findOrFail($request->variant_id);
            if ($variant->price) {
                $price = $variant->price;
            }
        }

        $cartItem = $cart->items()
            ->where('product_id', $request->product_id)
            ->where('variant_id', $request->variant_id)
            ->first();

        if ($cartItem) {
            $cartItem->quantity += $request->quantity;
            $cartItem->save();
        } else {
            $cartItem = $cart->items()->create([
                'product_id' => $request->product_id,
                'variant_id' => $request->variant_id,
                'quantity' => $request->quantity,
                'price' => $price,
            ]);
        }

        $cart->updateTotals();

        return response()->json([
            'message' => 'Item added to cart',
            'cart' => $cart->load(['items.product', 'items.variant'])
        ]);
    }

    public function update(Request $request, $itemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = $this->getCart($request);
        if (!$cart) return response()->json(['message' => 'Cart not found'], 404);

        $cartItem = $cart->items()->findOrFail($itemId);
        $cartItem->quantity = $request->quantity;
        $cartItem->save();

        $cart->updateTotals();

        return response()->json([
            'message' => 'Cart updated',
            'cart' => $cart->load(['items.product', 'items.variant'])
        ]);
    }

    public function destroy(Request $request, $itemId)
    {
        $cart = $this->getCart($request);
        if (!$cart) return response()->json(['message' => 'Cart not found'], 404);

        $cartItem = $cart->items()->findOrFail($itemId);
        $cartItem->delete();

        $cart->updateTotals();

        return response()->json([
            'message' => 'Item removed from cart',
            'cart' => $cart->load(['items.product', 'items.variant'])
        ]);
    }

    public function clear(Request $request)
    {
        $cart = $this->getCart($request);
        if (!$cart) return response()->json(['message' => 'Cart not found'], 404);

        $cart->items()->delete();
        $cart->updateTotals();

        return response()->json([
            'message' => 'Cart cleared',
            'cart' => $cart->load(['items.product', 'items.variant'])
        ]);
    }

    public function applyCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $cart = $this->getCart($request);
        if (!$cart) return response()->json(['message' => 'Cart not found'], 404);

        if (strtoupper($request->code) === 'SAVE10') {
            $cart->coupon_code = 'SAVE10';
            $cart->discount_amount = 10.00;
            $cart->updateTotals();
            return response()->json(['message' => 'Coupon applied!', 'cart' => $cart->load(['items.product', 'items.variant'])]);
        }

        return response()->json(['message' => 'Invalid coupon code'], 422);
    }
}
