<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Ticket;
use App\Models\TicketMessage;

Ticket::whereNull('agent_id')->get()->each(function($t) {
    $r = TicketMessage::where('ticket_id', $t->id)
        ->whereHas('user', function($q) {
            $q->whereHas('roles', function($sq) {
                $sq->whereIn('name', ['support', 'admin']);
            });
        })
        ->orderBy('created_at', 'asc')
        ->first();

    if ($r) {
        $t->update(['agent_id' => $r->user_id]);
        echo "Assigned ticket #{$t->id} to agent #{$r->user_id}\n";
    }
});
