<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TicketService;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    protected $ticketService;

    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        if ($user->isSupportAgent() || $user->isAdmin()) {
            $tickets = $this->ticketService->getAllTickets($request->all());
        } else {
            $tickets = $this->ticketService->getCustomerTickets($user->id);
        }

        return response()->json(['success' => true, 'data' => $tickets]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'subject'  => 'required|string|max:255',
            'message'  => 'required|string',
            'category' => 'required|string',
            'order_id' => 'nullable|exists:orders,id',
            'priority' => 'nullable|string|in:Low,Medium,High,Urgent',
        ]);

        $data = $request->all();
        $data['customer_id'] = $request->user()->id;

        $ticket = $this->ticketService->createTicket($data);

        return response()->json(['success' => true, 'data' => $ticket], 201);
    }

    public function show($id, Request $request)
    {
        $ticket = \App\Models\Ticket::with(['customer', 'agent', 'messages.user', 'order'])->findOrFail($id);
        
        // Authorization check
        $user = $request->user();
        if (!$user->isSupportAgent() && !$user->isAdmin() && $ticket->customer_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json(['success' => true, 'data' => $ticket]);
    }

    public function addMessage($id, Request $request)
    {
        $request->validate([
            'message'     => 'required|string',
            'is_internal' => 'nullable|boolean',
        ]);

        $ticket = \App\Models\Ticket::findOrFail($id);
        $user = $request->user();

        // Authorization check
        if (!$user->isSupportAgent() && !$user->isAdmin() && $ticket->customer_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $data = [
            'user_id'     => $user->id,
            'message'     => $request->message,
            'is_internal' => $request->is_internal ?? false,
        ];

        $message = $this->ticketService->addMessageToTicket($id, $data);

        return response()->json(['success' => true, 'data' => $message], 201);
    }

    public function updateStatus($id, Request $request)
    {
        $request->validate(['status' => 'required|string|in:Open,Pending,Resolved,Closed']);
        
        $ticket = $this->ticketService->updateTicketStatus($id, $request->status);

        return response()->json(['success' => true, 'data' => $ticket]);
    }

    public function submitSurvey($id, Request $request)
    {
        $request->validate([
            'rating'   => 'required|integer|min:1|max:5',
            'comments' => 'nullable|string',
        ]);

        $ticket = \App\Models\Ticket::findOrFail($id);
        
        $survey = \App\Models\TicketSurvey::create([
            'ticket_id' => $id,
            'rating'    => $request->rating,
            'comments'  => $request->comments,
        ]);

        return response()->json(['success' => true, 'data' => $survey], 201);
    }
}
