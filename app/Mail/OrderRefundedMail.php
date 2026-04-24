<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderRefundedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Refund Processed for Order #{$this->order->id} — ShopPro",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.order-refunded',
            with: ['order' => $this->order],
        );
    }
}
