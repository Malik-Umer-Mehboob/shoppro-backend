<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\LiveChatService;
use Illuminate\Http\Request;

class LiveChatController extends Controller
{
    protected $chatService;

    public function __construct(LiveChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function startChat(Request $request)
    {
        $customerId = $request->user()->id;
        $sessionId = $this->chatService->startChat($customerId);

        return response()->json(['success' => true, 'session_id' => $sessionId]);
    }

    public function assignChat($sessionId, Request $request)
    {
        $agentId = $request->user()->id;
        $success = $this->chatService->assignChatToAgent($sessionId, $agentId);

        return response()->json(['success' => $success]);
    }

    public function endChat($sessionId)
    {
        $success = $this->chatService->endChat($sessionId);
        return response()->json(['success' => $success]);
    }
}
