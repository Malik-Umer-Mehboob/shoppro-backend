<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:1|max:100',
        ]);

        $query = trim($request->q);

        // Search Products
        $products = Product::where('name', 'like', "%{$query}%")
            ->orWhere('sku', 'like', "%{$query}%")
            ->limit(5)
            ->get(['id', 'name', 'sku', 'price', 'thumbnail', 'status'])
            ->map(function ($p) {
                return [
                    'type' => 'product',
                    'id' => $p->id,
                    'title' => $p->name,
                    'subtitle' => 'SKU: ' . $p->sku . ' | Rs. ' . number_format($p->price),
                    'badge' => $p->status,
                    'thumbnail' => $p->thumbnail
                        ? asset('storage/' . $p->thumbnail)
                        : null,
                    'url' => '/admin/products',
                ];
            });

        // Search Orders
        $orders = Order::with('user')
            ->where('id', 'like', "%{$query}%")
            ->orWhereHas('user', function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(function ($o) {
                return [
                    'type' => 'order',
                    'id' => $o->id,
                    'title' => '#' . str_pad($o->id, 4, '0', STR_PAD_LEFT),
                    'subtitle' => ($o->user->name ?? 'Guest')
                        . ' | Rs. ' . number_format($o->grand_total)
                        . ' | ' . ucfirst($o->status),
                    'badge' => $o->status,
                    'thumbnail' => null,
                    'url' => '/admin/orders',
                ];
            });

        // Search Users (exclude admin)
        $users = User::with('roles')
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'admin');
            })
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get()
            ->map(function ($u) {
                return [
                    'type' => 'user',
                    'id' => $u->id,
                    'title' => $u->name,
                    'subtitle' => $u->email
                        . ' | ' . ucfirst($u->getRoleNames()->first() ?? 'user'),
                    'badge' => $u->getRoleNames()->first(),
                    'thumbnail' => $u->avatar
                        ? asset('storage/' . $u->avatar)
                        : null,
                    'url' => '/admin/users',
                ];
            });

        $results = collect()
            ->merge($products)
            ->merge($orders)
            ->merge($users);

        return response()->json([
            'success' => true,
            'data' => [
                'results' => $results,
                'counts' => [
                    'products' => $products->count(),
                    'orders' => $orders->count(),
                    'users' => $users->count(),
                    'total' => $results->count(),
                ],
                'query' => $query,
            ]
        ]);
    }
}
