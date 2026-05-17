<?php

namespace App\Services;

use App\Jobs\SendWelcomeEmailJob;
use App\Jobs\SendOrderConfirmationJob;
use App\Jobs\SendInvoiceEmailJob;
use App\Jobs\SendOtpEmailJob;
use App\Models\User;
use App\Models\Order;
use App\Helpers\EmailHelper;

class MailService
{
    public function sendWelcomeEmail(User $user)
    {
        SendWelcomeEmailJob::dispatch($user);
    }

    public function sendOrderConfirmation(Order $order)
    {
        SendOrderConfirmationJob::dispatch($order);
    }

    public function sendInvoiceEmail(Order $order)
    {
        SendInvoiceEmailJob::dispatch($order);
    }

    public function sendOtpEmail($email, $otp)
    {
        SendOtpEmailJob::dispatch($email, $otp);
    }

    public function sendTemplate($templateKey, $toEmail, $toName, $variables = [])
    {
        return EmailHelper::sendTemplate($templateKey, $toEmail, $toName, $variables);
    }
}
