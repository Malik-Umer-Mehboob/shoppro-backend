<?php

namespace App\Http\Controllers\Api\Support;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupportDashboardController extends Controller
{
    public function index(Request $request)
    {
        Log::info('Support Dashboard stats requested.');
        $today = now()->toDateString();
        $agentId = $request->user()->id;

        // Overall Counts (Global for the system)
        $totalTickets = DB::table('tickets')->count();
        Log::info('Total Tickets Count: ' . $totalTickets);
        
        $openTickets = DB::table('tickets')
            ->whereRaw('LOWER(status) = ?', ['open'])
            ->count();
        Log::info('Open Tickets Count: ' . $openTickets);

        $inProgressTickets = DB::table('tickets')
            ->where(function($q) {
                $q->whereRaw('LOWER(status) = ?', ['in progress'])
                  ->orWhereRaw('LOWER(status) = ?', ['pending']);
            })
            ->count();
        Log::info('In Progress Tickets Count: ' . $inProgressTickets);

        $resolvedTickets = DB::table('tickets')
            ->whereRaw('LOWER(status) = ?', ['resolved'])
            ->count();
        Log::info('Resolved Tickets Count: ' . $resolvedTickets);

        $closedTickets = DB::table('tickets')
            ->whereRaw('LOWER(status) = ?', ['closed'])
            ->count();
        Log::info('Closed Tickets Count: ' . $closedTickets);

        // Agent Specific Metrics (Last 7 Days)
        // If admin, show global performance, if support, show their own
        $isAdmin = $request->user()->isAdmin();
        
        $performanceMetrics = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $query = DB::table('tickets')->whereDate('created_at', $date);
            
            if (!$isAdmin) {
                $query->where('agent_id', $agentId);
            }
            
            $count = $query->count();
            
            $performanceMetrics[] = [
                'date' => $date,
                'day' => now()->subDays($i)->format('D'),
                'count' => $count
            ];
        }

        $agentTicketsQuery = DB::table('tickets');
        if (!$isAdmin) {
            $agentTicketsQuery->where('agent_id', $agentId);
        }
        $totalHandledByAgent = $agentTicketsQuery->count();

        // Today's Stats
        $totalToday = DB::table('tickets')
            ->whereDate('created_at', $today)
            ->count();

        $resolvedToday = DB::table('tickets')
            ->whereDate('created_at', $today)
            ->whereRaw('LOWER(status) = ?', ['resolved'])
            ->count();

        $activeChats = DB::table('tickets')
            ->whereRaw('LOWER(status) = ?', ['open'])
            ->whereDate('updated_at', $today)
            ->count();

        $shiftProgress = $totalToday > 0
            ? round(($resolvedToday / $totalToday) * 100)
            : 0;

        return response()->json([
            'success' => true,
            'data' => [
                'total_tickets' => $totalTickets,
                'open_tickets' => $openTickets,
                'in_progress_tickets' => $inProgressTickets,
                'resolved_tickets' => $resolvedTickets,
                'closed_tickets' => $closedTickets,
                'resolved_today' => $resolvedToday,
                'total_today' => $totalToday,
                'shift_progress' => $shiftProgress,
                'active_chats' => $activeChats,
                'performance_metrics' => $performanceMetrics,
                'total_handled_by_agent' => $totalHandledByAgent,
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
