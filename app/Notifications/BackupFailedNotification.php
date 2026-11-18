<?php

namespace App\Notifications;

use App\Notifications\Contracts\DynamicNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;

class BackupFailedNotification extends DynamicNotificationBase implements DynamicNotification
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->greeting('Hi!')
            ->line('The backup process failed!')
            ->line('Backup: '.$this->data['name'])
            ->line('Reason: '.$this->data['reason'])
            ->error();
    }

    public function toSlack($notifiable): SlackMessage
    {
        return parent::toSlack($notifiable)
            ->content("🚨 Backup process failed.\n\n - *Backup:* {$this->data['name']}\n - *Reason:* {$this->data['reason']}")
            ->error();
    }
}
