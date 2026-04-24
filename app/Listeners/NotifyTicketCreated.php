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
        $this->notificationService->sendNotification($customer->id, 'ticket_created', [
            'ticket_id' => $ticket->id,
            'subject'   => $ticket->subject,
            'message'   => "Your ticket #{$ticket->id} has been created successfully."
        ]);

        // Auto-assign if possible (e.g. to a support agent)
        $agent = User::where('role', 'support')->inRandomOrder()->first();
        if ($agent) {
            $ticket->update(['agent_id' => $agent->id]);
            
            // Notify agent
            $this->notificationService->sendNotification($agent->id, 'ticket_assigned', [
                'ticket_id' => $ticket->id,
                'customer'  => $customer->name,
                'message'   => "New ticket #{$ticket->id} assigned to you: {$ticket->subject}"
            ]);
        }
    }
}
