<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\NewsletterService;
use Illuminate\Http\Request;

class NewsletterController extends Controller
{
    protected $newsletterService;

    public function __construct(NewsletterService $newsletterService)
    {
        $this->newsletterService = $newsletterService;
    }

    public function index()
    {
        return response()->json([
            'success' => true,
            'data'    => \App\Models\Newsletter::latest()->get()
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject'      => 'required|string',
            'content'      => 'required|string',
            'scheduled_at' => 'nullable|date',
            'status'       => 'string|in:draft,scheduled',
        ]);

        $newsletter = $this->newsletterService->createNewsletter($data);

        return response()->json(['success' => true, 'data' => $newsletter], 201);
    }

    public function send($id)
    {
        $newsletter = $this->newsletterService->sendNewsletterToSubscribers($id);
        return response()->json(['success' => true, 'data' => $newsletter]);
    }

    public function subscribers()
    {
        $subscribers = \App\Models\User::where('subscribed_to_newsletter', true)->get(['id', 'name', 'email', 'created_at']);
        
        return response()->json([
            'success' => true,
            'data'    => $subscribers
        ]);
    }
}
