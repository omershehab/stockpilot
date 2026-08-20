<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SupportTicketActivity extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $title,
        private readonly string $message,
        private readonly array $messageParameters,
        private readonly string $url,
        private readonly string $ticketReference,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title,
            'message' => $this->message,
            'message_parameters' => $this->messageParameters,
            'url' => $this->url,
            'ticket_reference' => $this->ticketReference,
        ];
    }
}
