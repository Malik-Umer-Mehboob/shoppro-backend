<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GiftCard;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GiftCardController extends Controller
{
    public function index()
    {
        return response()->json(GiftCard::where('buyer_id', auth()->id())->latest()->get());
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:5',
            'recipient_email' => 'required|email',
            'message' => 'nullable|string',
        ]);

        $giftCard = GiftCard::create([
            'code' => strtoupper(Str::random(12)),
            'initial_amount' => $request->amount,
            'balance' => $request->amount,
            'buyer_id' => auth()->id(),
            'recipient_email' => $request->recipient_email,
            'message' => $request->message,
            'expires_at' => now()->addYear(),
        ]);

        return response()->json($giftCard, 201);
    }

    public function checkBalance(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        
        $giftCard = GiftCard::where('code', $request->code)->first();
        
        if (!$giftCard || !$giftCard->isValid()) {
            return response()->json(['message' => 'Invalid or expired gift card.'], 404);
        }

        return response()->json([
            'balance' => $giftCard->balance,
            'expires_at' => $giftCard->expires_at,
        ]);
    }

    public function redeem(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'amount_to_redeem' => 'required|numeric|min:0.01'
        ]);

        $giftCard = GiftCard::where('code', $request->code)->first();

        if (!$giftCard || !$giftCard->isValid()) {
            return response()->json(['message' => 'Invalid gift card.'], 404);
        }

        if ($giftCard->balance < $request->amount_to_redeem) {
            return response()->json(['message' => 'Insufficient balance.'], 422);
        }

        $giftCard->decrement('balance', $request->amount_to_redeem);

        return response()->json(['message' => 'Gift card redeemed successfully.', 'new_balance' => $giftCard->balance]);
    }
}
