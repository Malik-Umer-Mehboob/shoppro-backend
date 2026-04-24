<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class LiveChatService
{
    /**
     * In a real implementation, this would interact with a WebSocket server (Pusher, Reverb, etc.)
     * or a third-party service. For this demo, we'll simulate chat session management.
     */

    public function startChat($customerId)
    {
        $sessionId = 'chat_' . $customerId . '_' . time();
        Cache::put("chat_session_{$sessionId}", [
            'customer_id' => $customerId,
            'agent_id'    => null,
            'status'      => 'waiting',
            'started_at'  => now(),
        ], 3600);

        return $sessionId;
    }

    public function assignChatToAgent($sessionId, $agentId)
    {
        $session = Cache::get("chat_session_{$sessionId}");
        if ($session) {
            $session['agent_id'] = $agentId;
            $session['status'] = 'active';
            Cache::put("chat_session_{$sessionId}", $session, 3600);
            return true;
        }
        return false;
    }

    public function endChat($sessionId)
    {
        $session = Cache::get("chat_session_{$sessionId}");
        if ($session) {
            $session['status'] = 'ended';
            $session['ended_at'] = now();
            Cache::put("chat_session_{$sessionId}", $session, 3600);
            // Optionally save transcript to database
            return true;
        }
        return false;
    }

    public function getAvailableAgents()
    {
        // For simplicity, find all users with support role
        return User::where('role', 'support')->get();
    }
}
