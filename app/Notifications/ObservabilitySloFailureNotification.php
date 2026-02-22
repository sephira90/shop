<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ObservabilitySloFailureNotification extends Notification
{
    use Queueable;

    /**
     * Create a notification instance.
     *
     * @param  list<string>  $lines
     */
    public function __construct(
        private readonly string $subject,
        private readonly array $lines,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject($this->subject);

        foreach ($this->lines as $line) {
            $message->line($line);
        }

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array{subject:string,lines:list<string>}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'subject' => $this->subject,
            'lines' => $this->lines,
        ];
    }
}
