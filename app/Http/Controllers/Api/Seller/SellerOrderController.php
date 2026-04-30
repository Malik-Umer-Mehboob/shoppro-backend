<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;

class SellerOrderController extends Controller
{
    public function index()
    {
        $seller = auth()->user();
        $sellerProductIds = Product::where('seller_id', $seller->id)->pluck('id');

        $orders = OrderItem::with(['order.user', 'product'])
            ->whereIn('product_id', $sellerProductIds)
            ->latest()
            ->paginate(15);

        $mapped = $orders->through(function($item) {
            return [
                'id' => $item->id,
                'order_id' => $item->order_id,
                'order_number' => '#' . str_pad($item->order_id, 4, '0', STR_PAD_LEFT),
                'product_name' => $item->product->name ?? 'N/A',
                'product_thumbnail' => $item->product->thumbnail 
                    ? asset('storage/' . $item->product->thumbnail) 
                    : null,
                'customer_name' => $item->order?->user?->name ?? 'Guest',
                'customer_email' => $item->order?->user?->email ?? '',
                'quantity' => $item->quantity,
                'price' => $item->price,
                'total' => $item->total,
                'status' => $item->order->status,
                'payment_status' => $item->order->payment_status ?? 'pending',
                'created_at' => $item->order->created_at->format('M d, Y'),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $mapped,
        ]);
    }
}
