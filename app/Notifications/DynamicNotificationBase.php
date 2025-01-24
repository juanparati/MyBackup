<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

abstract class DynamicNotificationBase extends Notification
{
    public function __construct(protected array $settings, protected array $data = []) {}

    public function via($notifiable): array
    {
        return array_keys($this->settings);
    }

    public function routeNotificationForSlack($notification)
    {
        return $this->settings['slack']['webhook'] ?? '';
    }

    public function toSlack($notifiable): SlackMessage
    {
        $slackMsg = new SlackMessage;

        if ($this->settings['slack']['username'] ?? null) {
            $slackMsg->username = $this->settings['slack']['username'];
        }

        if ($this->settings['slack']['channel'] ?? null) {
            $slackMsg->channel = $this->settings['slack']['channel'];
        }

        return $slackMsg;
    }
}
