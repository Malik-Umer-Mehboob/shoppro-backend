<?php
namespace App\Helpers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailHelper
{
    public static function sendTemplate(
        string $templateKey,
        string $toEmail,
        string $toName,
        array $variables = []
    ): bool {
        try {
            // Get template from database
            $template = DB::table('email_templates')
                ->where('name', $templateKey)
                ->where('is_active', true)
                ->first();

            if (!$template) {
                Log::warning("Email template not found: {$templateKey}");
                return false;
            }

            // Replace variables in subject and content
            $subject = self::replaceVariables(
                $template->subject, $variables
            );
            $content = self::replaceVariables(
                $template->content, $variables
            );

            // Send email
            Mail::send([], [], function ($message)
                use ($toEmail, $toName, $subject, $content) {
                $message
                    ->to($toEmail, $toName)
                    ->subject($subject)
                    ->html($content);
            });

            return true;
        } catch (\Exception $e) {
            Log::error("Email send failed [{$templateKey}]: "
                . $e->getMessage());
            return false;
        }
    }

    private static function replaceVariables(
        string $content,
        array $variables
    ): string {
        foreach ($variables as $key => $value) {
            $content = str_replace(
                ['{{' . $key . '}}', '{{ ' . $key . ' }}'],
                $value,
                $content
            );
        }
        return $content;
    }
}
