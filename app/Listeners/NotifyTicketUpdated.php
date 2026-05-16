<?php

namespace App\Listeners;

use App\Events\TicketUpdated;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyTicketUpdated implements ShouldQueue
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function handle(TicketUpdated $event): void
    {
        $ticket = $event->ticket;
        $customer = $ticket->customer;

        // Notify customer of update/reply
        \App\Services\NotificationService::send(
            $customer->id,
            'Ticket Updated 🎫',
            "Your ticket #{$ticket->id} has a new update. Status: {$ticket->status}",
            'ticket.updated',
            \App\Services\NotificationService::PRIORITY_MEDIUM,
            ['ticket_id' => $ticket->id],
            "/help"
        );

        // Send survey if resolved
        if ($ticket->status === 'Resolved') {
            \App\Services\NotificationService::send(
                $customer->id,
                'How was your experience? ⭐',
                "Please rate our support for ticket #{$ticket->id}.",
                'ticket.survey',
                \App\Services\NotificationService::PRIORITY_MEDIUM,
                ['ticket_id' => $ticket->id],
                "/help"
            );
        }

        // Notify agent if update is from customer
        if ($ticket->agent_id) {
            \App\Services\NotificationService::send(
                $ticket->agent_id,
                'Ticket Updated 🎫',
                "Ticket #{$ticket->id} ({$ticket->subject}) has been updated.",
                'ticket.updated',
                \App\Services\NotificationService::PRIORITY_HIGH,
                ['ticket_id' => $ticket->id],
                "/support/tickets"
            );
        }
    }
}
