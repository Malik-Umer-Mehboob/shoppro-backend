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
            'ticket.updated',
            'Ticket Updated 🎫',
            "Your ticket #{$ticket->id} has a new update. Status: {$ticket->status}",
            ['ticket_id' => $ticket->id],
            \App\Services\NotificationService::PRIORITY_MEDIUM,
            "/help"
        );

        \App\Helpers\EmailHelper::sendTemplate(
            'support_response_email',
            $customer->email,
            $customer->name,
            [
                'ticket_id' => $ticket->id,
                'status' => $ticket->status,
                'subject' => $ticket->subject,
                'name' => $customer->name,
            ]
        );

        // Send survey if resolved
        if ($ticket->status === 'Resolved') {
            \App\Services\NotificationService::send(
                $customer->id,
                'ticket.survey',
                'How was your experience? ⭐',
                "Please rate our support for ticket #{$ticket->id}.",
                ['ticket_id' => $ticket->id],
                \App\Services\NotificationService::PRIORITY_MEDIUM,
                "/help"
            );
        }

        // Notify agent if update is from customer
        if ($ticket->agent_id) {
            \App\Services\NotificationService::send(
                $ticket->agent_id,
                'ticket.updated',
                'Ticket Updated 🎫',
                "Ticket #{$ticket->id} ({$ticket->subject}) has been updated.",
                ['ticket_id' => $ticket->id],
                \App\Services\NotificationService::PRIORITY_HIGH,
                "/support/tickets"
            );
        }
    }
}
