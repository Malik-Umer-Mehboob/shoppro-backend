<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tickets = \App\Models\Ticket::whereNull('agent_id')->get();
        foreach ($tickets as $ticket) {
            // Find the first message from a user with support role
            $firstReply = \App\Models\TicketMessage::where('ticket_id', $ticket->id)
                ->whereHas('user', function ($query) {
                    $query->whereHas('roles', function ($q) {
                        $q->whereIn('name', ['support', 'admin']);
                    });
                })
                ->orderBy('created_at', 'asc')
                ->first();

            if ($firstReply) {
                $ticket->update(['agent_id' => $firstReply->user_id]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            //
        });
    }
};
