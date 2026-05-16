<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyTicketCreated implements ShouldQueue
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(TicketCreated $event): void
    {
        $ticket = $event->ticket;
        $customer = $ticket->customer;

        // Notify customer
        \App\Services\NotificationService::send(
            $customer->id,
            'Support Ticket Created ✅',
            "Your ticket #{$ticket->id} has been created successfully.",
            'ticket.created',
            \App\Services\NotificationService::PRIORITY_MEDIUM,
            ['ticket_id' => $ticket->id],
            '/help'
        );

        // Auto-assign if possible (e.g. to a support agent)
        $agent = User::where('role', 'support')->inRandomOrder()->first();
        if ($agent) {
            $ticket->update(['agent_id' => $agent->id]);
            
            // Notify agent
            \App\Services\NotificationService::send(
                $agent->id,
                'New Ticket Assigned 🎫',
                "New ticket #{$ticket->id} assigned to you: {$ticket->subject}",
                'ticket.assigned',
                \App\Services\NotificationService::PRIORITY_HIGH,
                ['ticket_id' => $ticket->id],
                '/support/tickets'
            );
        }
    }
}
