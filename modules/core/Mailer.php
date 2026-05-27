<?php
namespace OrbitAdmin\Core;

if (!defined('ORBIT_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

/**
 * Minimal mailer. Tries PHPMailer if available, otherwise falls back to mail().
 * In demo mode (APP_DEMO=true), writes the message to logs/mail.log instead of sending.
 */
final class Mailer
{
    /** @param array<string,string> $headers */
    public static function send(string $to, string $subject, string $body, array $headers = []): bool
    {
        if (Config::isDemo() || (bool) Config::get('MAIL_DISABLED', false)) {
            (new Logger(Config::get('LOG_PATH') . '/mail.log'))->info('MAIL (demo, not sent)', [
                'to' => $to,
                'subject' => $subject,
                'body_excerpt' => mb_substr($body, 0, 200),
            ]);
            return true;
        }
        $from = (string) Config::get('MAIL_FROM', 'no-reply@example.com');
        $fromName = (string) Config::get('MAIL_FROM_NAME', 'OrbitAdmin');
        $defaults = [
            'From' => sprintf('%s <%s>', $fromName, $from),
            'Reply-To' => $from,
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/html; charset=UTF-8',
            'X-Mailer' => 'OrbitAdmin/0.1.0',
        ];
        $merged = array_merge($defaults, $headers);
        $lines = [];
        foreach ($merged as $k => $v) {
            $lines[] = $k . ': ' . preg_replace('/[\r\n]+/', ' ', (string) $v);
        }
        $headerStr = implode("\r\n", $lines);
        return @mail($to, $subject, $body, $headerStr);
    }

    /**
     * Replace {{var}} placeholders in a template with values.
     * @param array<string,string> $vars
     */
    public static function render(string $template, array $vars): string
    {
        return preg_replace_callback('/{{\s*([a-zA-Z_][a-zA-Z0-9_\.]*)\s*}}/', static function ($m) use ($vars) {
            return isset($vars[$m[1]]) ? (string) $vars[$m[1]] : '';
        }, $template) ?? $template;
    }
}
