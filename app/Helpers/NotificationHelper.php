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
        try {
            DB::table('notifications')->insert([
                'id'             => Str::uuid()->toString(),
                'type'           => $type,
                'notifiable_type'=> 'App\\Models\\User',
                'notifiable_id'  => $userId,
                'data'           => json_encode([
                    'title'   => $title,
                    'message' => $message,
                    'icon'    => static::getIcon($type),
                    'url'     => $data['url'] ?? null,
                    'extra'   => $data,
                ]),
                'read_at'    => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Notification failed: ' . $e->getMessage());
        }
    }

    public static function sendToRole(
        string $role,
        string $type,
        string $title,
        string $message,
        array $data = []
    ): void {
        try {
            $users = \App\Models\User::whereHas(
                'roles',
                fn($q) => $q->where('name', $role)
            )->get(['id']);

            foreach ($users as $user) {
                static::send($user->id, $type, $title, $message, $data);
            }
        } catch (\Exception $e) {
            \Log::error('Role notification failed: ' . $e->getMessage());
        }
    }

    private static function getIcon(string $type): string
    {
        return match (true) {
            str_contains($type, 'order')    => '📦',
            str_contains($type, 'user')     => '👤',
            str_contains($type, 'stock')    => '⚠️',
            str_contains($type, 'ticket')   => '🎫',
            str_contains($type, 'return')   => '↩️',
            str_contains($type, 'delivery') => '🚚',
            str_contains($type, 'category') => '📁',
            str_contains($type, 'payment')  => '💰',
            str_contains($type, 'review')   => '⭐',
            default                         => '🔔',
        };
    }
}
