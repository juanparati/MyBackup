<?php

namespace App\Commands\Concerns;

use App\Helpers\NotificationSender;

trait NeedNotifications
{
    use NeedConfig;

    /**
     * Send notification.
     */
    protected function sendNotification(string $notification, array $data = []): bool
    {
        return (new NotificationSender($this->config->notifications ?? []))
            ->send($notification, $data);
    }
}
