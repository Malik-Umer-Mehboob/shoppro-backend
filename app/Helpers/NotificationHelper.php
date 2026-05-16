<?php

namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class NotificationHelper
{
    public static function send(
        int $userId,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): void {
        \App\Services\NotificationService::send(
            $userId, 
            $type, 
            $title, 
            $message, 
            $data, 
            \App\Services\NotificationService::PRIORITY_MEDIUM, 
            $data['url'] ?? null
        );
    }

    public static function sendToRole(
        string $role,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): void {
        \App\Services\NotificationService::sendToRole(
            $role, 
            $type, 
            $title, 
            $message, 
            \App\Services\NotificationService::PRIORITY_MEDIUM, 
            $data, 
            $data['url'] ?? null
        );
    }
}
