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
                'attachment'  => $data['attachment'] ?? null,
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

    public function updateTicketStatus($ticketId, $status, $agentId = null)
    {
        $ticket = Ticket::findOrFail($ticketId);
        $updateData = ['status' => $status];
        if ($agentId && !$ticket->agent_id) {
            $updateData['agent_id'] = $agentId;
        }
        $ticket->update($updateData);
        event(new TicketUpdated($ticket));
        return $ticket;
    }

    public function addMessageToTicket($ticketId, array $data)
    {
        return DB::transaction(function () use ($ticketId, $data) {
            $ticket = Ticket::findOrFail($ticketId);
            
            // Auto-assign agent if they are the one replying
            $user = \App\Models\User::find($data['user_id']);
            if ($user && ($user->isSupportAgent() || $user->isAdmin()) && !$ticket->agent_id) {
                $ticket->agent_id = $user->id;
                $ticket->save();
            }

            $message = $ticket->messages()->create([
                'user_id'     => $data['user_id'],
                'message'     => $data['message'],
                'attachment'  => $data['attachment'] ?? null,
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

        if (isset($filters['search']) && !empty(trim($filters['search']))) {
            $searchTerm = trim($filters['search']);
            
            // Check if it's a ticket ID format (numeric, #123, TICKET-123)
            if (preg_match('/^(#|TICKET-)?(\d+)$/i', $searchTerm, $matches)) {
                $query->where('id', $matches[2]);
            } else {
                $query->where(function($q) use ($searchTerm) {
                    $q->where('subject', 'like', "%{$searchTerm}%")
                      ->orWhereHas('customer', function($q2) use ($searchTerm) {
                          $q2->where('name', 'like', "%{$searchTerm}%");
                      });
                });
            }
        }

        return $query->with(['customer', 'agent'])->orderBy('updated_at', 'desc')->get();
    }
}
