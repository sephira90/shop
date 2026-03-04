<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusChangedNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $orderNumber,
        private readonly string $previousStatus,
        private readonly string $currentStatus,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(sprintf('Order %s status updated', $this->orderNumber))
            ->line(sprintf('Order status changed from "%s" to "%s".', $this->previousStatus, $this->currentStatus));
    }

    /**
     * @return array{order_number:string,previous_status:string,current_status:string}
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_number' => $this->orderNumber,
            'previous_status' => $this->previousStatus,
            'current_status' => $this->currentStatus,
        ];
    }
}
