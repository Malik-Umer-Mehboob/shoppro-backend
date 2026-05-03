<?php

namespace App\Http\Controllers\Api\Support;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupportDashboardController extends Controller
{
    public function index()
    {
        $today = now()->toDateString();

        $totalToday = DB::table('tickets')
            ->whereDate('created_at', $today)
            ->count();

        $resolvedToday = DB::table('tickets')
            ->whereDate('created_at', $today)
            ->where('status', 'Resolved')
            ->count();

        $shiftProgress = $totalToday > 0
            ? round(($resolvedToday / $totalToday) * 100)
            : 0;

        $openTickets = DB::table('tickets')
            ->where('status', 'Open')
            ->count();

        $pendingTickets = DB::table('tickets')
            ->where('status', 'Pending')
            ->count();

        $activeChats = DB::table('tickets')
            ->where('status', 'Open')
            ->whereDate('updated_at', $today)
            ->count();

        return response()->json([
            'success' => true,
            'data' => [
                'shift_progress' => $shiftProgress,
                'open_tickets' => $openTickets,
                'pending_tickets' => $pendingTickets,
                'resolved_today' => $resolvedToday,
                'total_today' => $totalToday,
                'active_chats' => $activeChats,
            ]
        ]);
    }

    public function search(Request $request)
    {
        $request->validate([
            'q' => 'required|string|min:2|max:100',
        ]);

        $query = trim($request->q);
        $results = [];

        // Search Tickets
        $tickets = DB::table('tickets')
            ->where(function ($q) use ($query) {
                $q->where('subject', 'like', "%{$query}%")
                  ->orWhere('id', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get(['id', 'subject', 'status', 'priority']);

        foreach ($tickets as $ticket) {
            $results[] = [
                'type' => 'ticket',
                'title' => '#' . $ticket->id . ' — ' . $ticket->subject,
                'subtitle' => 'Ticket • '
                    . ucfirst($ticket->status)
                    . ' • ' . ucfirst($ticket->priority),
                'url' => '/support/tickets',
            ];
        }

        // Search Customers
        $customers = \App\Models\User::whereHas('roles',
            fn($q) => $q->where('name', 'customer'))
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->limit(5)
            ->get(['id', 'name', 'email']);

        foreach ($customers as $customer) {
            $results[] = [
                'type' => 'customer',
                'title' => $customer->name,
                'subtitle' => 'Customer • ' . $customer->email,
                'url' => '/support/customers',
            ];
        }

        // Search Knowledge Base
        $articles = DB::table('knowledge_base_articles')
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('content', 'like', "%{$query}%");
            })
            ->limit(3)
            ->get(['id', 'title', 'category']);

        foreach ($articles as $article) {
            $results[] = [
                'type' => 'article',
                'title' => $article->title,
                'subtitle' => 'Article • ' . $article->category,
                'url' => '/support/kb',
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $results,
        ]);
    }
}
