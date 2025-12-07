<?php
// core/helpers/mailer.php
//
// Lightweight mail helper used for verification codes.
// Falls back to logging locally when APP_ENV=local to avoid spamming real inboxes during development.

function send_verification_code_email(string $to, string $code, string $purpose = 'Account verification'): bool
{
    $env = getenv('APP_ENV') ?: 'production';
    $subject = 'Your verification code';
    $message = "This is your {$purpose} code: {$code}\n\n"
        . "The code expires in 15 minutes. If you did not request this, you can ignore this email.\n";

    if (strtolower($env) === 'local') {
        // Log instead of sending real mail in local/dev environments.
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }
        $logFile = $logDir . '/mail.log';
        $line = '[' . date('Y-m-d H:i:s') . "] To: {$to} | {$subject} | Code: {$code}\n";
        @file_put_contents($logFile, $line, FILE_APPEND);
        return true;
    }

    $headers = [
        'From: no-reply@paterostechnologicalcollege.edu.ph',
        'Reply-To: no-reply@paterostechnologicalcollege.edu.ph',
        'X-Mailer: PHP/' . phpversion(),
    ];

    return mail($to, $subject, $message, implode("\r\n", $headers));
}
