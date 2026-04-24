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
        $this->notificationService->sendNotification($customer->id, 'ticket_updated', [
            'ticket_id' => $ticket->id,
            'status'    => $ticket->status,
            'message'   => "Your ticket #{$ticket->id} has a new update. Status: {$ticket->status}"
        ]);

        // Send survey if resolved
        if ($ticket->status === 'Resolved') {
            $this->notificationService->sendNotification($customer->id, 'ticket_survey', [
                'ticket_id' => $ticket->id,
                'message'   => "How was your experience? Please rate our support for ticket #{$ticket->id}."
            ]);
        }

        // Notify agent if update is from customer
        if ($ticket->agent_id) {
            $this->notificationService->sendNotification($ticket->agent_id, 'ticket_updated', [
                'ticket_id' => $ticket->id,
                'message'   => "Ticket #{$ticket->id} ({$ticket->subject}) has been updated."
            ]);
        }
    }
}
