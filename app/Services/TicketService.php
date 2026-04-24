<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Events\TicketCreated;
use App\Events\TicketUpdated;
use Illuminate\Support\Facades\DB;

class TicketService
{
    public function createTicket(array $data)
    {
        return DB::transaction(function () use ($data) {
            $ticket = Ticket::create([
                'customer_id' => $data['customer_id'],
                'order_id'    => $data['order_id'] ?? null,
                'category'    => $data['category'],
                'priority'    => $data['priority'] ?? 'Low',
                'subject'     => $data['subject'],
                'message'     => $data['message'],
                'status'      => 'Open',
            ]);

            event(new TicketCreated($ticket));

            return $ticket;
        });
    }

    public function assignTicketToAgent($ticketId, $agentId)
    {
        $ticket = Ticket::findOrFail($ticketId);
        $ticket->update(['agent_id' => $agentId]);
        event(new TicketUpdated($ticket));
        return $ticket;
    }

    public function updateTicketStatus($ticketId, $status)
    {
        $ticket = Ticket::findOrFail($ticketId);
        $ticket->update(['status' => $status]);
        event(new TicketUpdated($ticket));
        return $ticket;
    }

    public function addMessageToTicket($ticketId, array $data)
    {
        return DB::transaction(function () use ($ticketId, $data) {
            $ticket = Ticket::findOrFail($ticketId);
            $message = $ticket->messages()->create([
                'user_id'     => $data['user_id'],
                'message'     => $data['message'],
                'is_internal' => $data['is_internal'] ?? false,
            ]);

            $ticket->touch();
            event(new TicketUpdated($ticket));

            return $message;
        });
    }

    public function closeTicket($ticketId)
    {
        return $this->updateTicketStatus($ticketId, 'Closed');
    }

    public function getCustomerTickets($customerId)
    {
        return Ticket::where('customer_id', $customerId)->orderBy('updated_at', 'desc')->get();
    }

    public function getAgentTickets($agentId)
    {
        return Ticket::where('agent_id', $agentId)->orderBy('updated_at', 'desc')->get();
    }

    public function getAllTickets($filters = [])
    {
        $query = Ticket::query();

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (isset($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        return $query->with(['customer', 'agent'])->orderBy('updated_at', 'desc')->get();
    }
}
