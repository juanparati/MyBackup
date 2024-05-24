<?php

namespace App\Notifications;

use App\Notifications\Contracts\DynamicNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;

class BackupFinishedNotification extends DynamicNotificationBase implements DynamicNotification
{
    public function __construct(array $settings, array $data = [])
    {
        parent::__construct($settings, $data);
        $this->data['size'] /= 1024;
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->greeting('Hi!')
            ->line('The backup process was successfully executed!')
            ->line('Snapshot: '.$this->data['snapshot'])
            ->line('CRC: '.$this->data['crc'])
            ->line('Size: '.$this->data['size'].'KB')
            ->success();
    }

    public function toSlack($notifiable): SlackMessage
    {
        return parent::toSlack($notifiable)
            ->content("👍 Backup process was successfully executed.\n\n - *Snapshot:* {$this->data['snapshot']}\n - *CRC:* {$this->data['crc']}\n - *Size:* {$this->data['size']} KB")
            ->success();
    }
}
