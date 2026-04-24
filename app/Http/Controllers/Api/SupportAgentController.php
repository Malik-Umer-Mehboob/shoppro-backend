<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class SupportAgentController extends Controller
{
    public function dashboardMetrics()
    {
        // For support agents and admins
        return response()->json([
            'success' => true,
            'data' => [
                'open_tickets' => \App\Models\Ticket::where('status', 'Open')->count(),
                'pending_tickets' => \App\Models\Ticket::where('status', 'Pending')->count(),
                'resolved_today' => \App\Models\Ticket::where('status', 'Resolved')->whereDate('updated_at', today())->count(),
                'active_chats' => 5, // Simulated
            ]
        ]);
    }

    public function agentsList()
    {
        $agents = User::where('role', 'support')->get(['id', 'name', 'email']);
        return response()->json(['success' => true, 'data' => $agents]);
    }

    public function orderLookup(Request $request)
    {
        $request->validate([
            'order_number' => 'required|string',
            'email'        => 'required|email',
        ]);

        // Find order by ID and customer email
        $order = Order::with(['items.product', 'items.variant'])
            ->where('id', $request->order_number) // In this app, order ID is the number
            ->whereHas('user', function ($q) use ($request) {
                $q->where('email', $request->email);
            })
            ->first();

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found or email does not match'], 404);
        }

        return response()->json(['success' => true, 'data' => $order]);
    }
}
