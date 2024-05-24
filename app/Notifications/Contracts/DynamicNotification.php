<?php

namespace App\Notifications\Contracts;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;

interface DynamicNotification
{
    public function __construct(array $settings, array $data = []);

    public function via($notifiable): array;

    public function toMail($notifiable): MailMessage;

    public function toSlack($notifiable): SlackMessage;
}
