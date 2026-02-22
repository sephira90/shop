<?php

declare(strict_types=1);

namespace App\Support\Observability;

use App\Notifications\ObservabilitySloFailureNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Throwable;

class ObservabilityAlertRouter
{
    private const COOLDOWN_CACHE_KEY = 'observability:alerts:cooldown:last_failure_at';

    /**
     * Route failed observability check alert to configured channels.
     *
     * @param  array{
     *     command:string,
     *     exit_code:int,
     *     output:string,
     *     parameters:array<string,string>,
     *     happened_at:string
     * }  $payload
     * @return array{sent:list<string>,suppressed:bool}
     */
    public function routeFailureAlert(array $payload): array
    {
        if ($this->isSuppressedByCooldown()) {
            return ['sent' => [], 'suppressed' => true];
        }

        $subject = $this->buildSubject($payload);
        $lines = $this->buildMessageLines($payload);

        $sent = [];

        if ($this->sendEmailAlert($subject, $lines)) {
            $sent[] = 'email';
        }

        if ($this->sendSlackAlert($subject, $lines)) {
            $sent[] = 'slack';
        }

        if ($this->sendPagerDutyAlert($subject, $lines)) {
            $sent[] = 'pagerduty';
        }

        if ($sent !== []) {
            $this->rememberCooldown();
        }

        return ['sent' => $sent, 'suppressed' => false];
    }

    /**
     * Build alert subject.
     *
     * @param  array{command:string,exit_code:int,output:string,parameters:array<string,string>,happened_at:string}  $payload
     */
    private function buildSubject(array $payload): string
    {
        return sprintf(
            '[%s][%s] Observability SLO check failed: %s',
            (string) config('app.name', 'app'),
            (string) config('app.env', 'unknown'),
            $payload['command'],
        );
    }

    /**
     * Build alert message lines.
     *
     * @param  array{command:string,exit_code:int,output:string,parameters:array<string,string>,happened_at:string}  $payload
     * @return list<string>
     */
    private function buildMessageLines(array $payload): array
    {
        return [
            'Observability SLO check failed.',
            'Environment: '.(string) config('app.env', 'unknown'),
            'App URL: '.(string) config('app.url', 'n/a'),
            'Command: '.$payload['command'],
            'Exit code: '.(string) $payload['exit_code'],
            'Timestamp: '.$payload['happened_at'],
            'Parameters: '.json_encode($payload['parameters'], JSON_UNESCAPED_SLASHES),
            'Output: '.Str::limit($payload['output'], 1200),
        ];
    }

    /**
     * Send alert via mail notification routes.
     *
     * @param  list<string>  $lines
     */
    private function sendEmailAlert(string $subject, array $lines): bool
    {
        if (! (bool) config('observability.alerts.email.enabled', false)) {
            return false;
        }

        $configuredRecipients = config('observability.alerts.email.recipients', []);
        if (! is_array($configuredRecipients)) {
            $this->logRoutingWarning('email', 'Configured recipients value is not an array.');

            return false;
        }

        /** @var list<string> $recipients */
        $recipients = array_values(array_filter(array_map(
            static fn (mixed $value): string => trim((string) $value),
            $configuredRecipients,
        )));

        if ($recipients === []) {
            $this->logRoutingWarning('email', 'No recipients configured.');

            return false;
        }

        foreach ($recipients as $recipient) {
            Notification::route('mail', $recipient)
                ->notify(new ObservabilitySloFailureNotification($subject, $lines));
        }

        return true;
    }

    /**
     * Send alert via Slack incoming webhook.
     *
     * @param  list<string>  $lines
     */
    private function sendSlackAlert(string $subject, array $lines): bool
    {
        if (! (bool) config('observability.alerts.slack.enabled', false)) {
            return false;
        }

        $webhookUrl = trim((string) config('observability.alerts.slack.webhook_url', ''));
        if ($webhookUrl === '') {
            $this->logRoutingWarning('slack', 'Slack webhook URL is empty.');

            return false;
        }

        try {
            $response = Http::timeout(10)->post($webhookUrl, [
                'text' => "*{$subject}*\n".implode("\n", $lines),
            ]);
        } catch (Throwable $exception) {
            $this->logRoutingWarning('slack', 'Slack request threw an exception.', [
                'exception' => $exception->getMessage(),
            ]);

            return false;
        }

        if (! $response->successful()) {
            $this->logRoutingWarning('slack', 'Slack webhook request failed.', [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Send alert via PagerDuty Events API v2.
     *
     * @param  list<string>  $lines
     */
    private function sendPagerDutyAlert(string $subject, array $lines): bool
    {
        if (! (bool) config('observability.alerts.pagerduty.enabled', false)) {
            return false;
        }

        $integrationKey = trim((string) config('observability.alerts.pagerduty.integration_key', ''));
        if ($integrationKey === '') {
            $this->logRoutingWarning('pagerduty', 'PagerDuty integration key is empty.');

            return false;
        }

        $severity = strtolower(trim((string) config('observability.alerts.pagerduty.severity', 'warning')));
        if (! in_array($severity, ['critical', 'error', 'warning', 'info'], true)) {
            $severity = 'warning';
        }

        try {
            $response = Http::timeout(10)->post('https://events.pagerduty.com/v2/enqueue', [
                'routing_key' => $integrationKey,
                'event_action' => 'trigger',
                'payload' => [
                    'summary' => $subject,
                    'source' => (string) config('app.url', 'unknown'),
                    'severity' => $severity,
                    'custom_details' => [
                        'message' => implode("\n", $lines),
                    ],
                ],
            ]);
        } catch (Throwable $exception) {
            $this->logRoutingWarning('pagerduty', 'PagerDuty request threw an exception.', [
                'exception' => $exception->getMessage(),
            ]);

            return false;
        }

        if (! $response->successful()) {
            $this->logRoutingWarning('pagerduty', 'PagerDuty request failed.', [
                'status' => $response->status(),
                'body' => Str::limit($response->body(), 500),
            ]);

            return false;
        }

        return true;
    }

    /**
     * Determine if alert should be suppressed by cooldown policy.
     */
    private function isSuppressedByCooldown(): bool
    {
        $cooldownMinutes = max(0, (int) config('observability.alerts.cooldown_minutes', 30));

        return $cooldownMinutes > 0 && Cache::has(self::COOLDOWN_CACHE_KEY);
    }

    /**
     * Persist cooldown marker for alert storm suppression.
     */
    private function rememberCooldown(): void
    {
        $cooldownMinutes = max(0, (int) config('observability.alerts.cooldown_minutes', 30));
        if ($cooldownMinutes <= 0) {
            return;
        }

        Cache::put(self::COOLDOWN_CACHE_KEY, now()->timestamp, now()->addMinutes($cooldownMinutes));
    }

    /**
     * Emit routing warning with channel context.
     *
     * @param  array<string,mixed>  $context
     */
    private function logRoutingWarning(string $channel, string $message, array $context = []): void
    {
        Log::warning('observability.alert_routing_warning', [
            'channel' => $channel,
            'message' => $message,
            ...$context,
        ]);
    }
}
