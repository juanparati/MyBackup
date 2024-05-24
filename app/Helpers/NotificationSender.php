<?php

namespace App\Helpers;

use Illuminate\Notifications\AnonymousNotifiable;

class NotificationSender
{
    public function __construct(protected array $settings)
    {
        if ($this->settings['mail'] ?? null) {
            config(['mail.mailers.smtp' => $this->settings['mail']]);
        }
    }

    public function send(string $notification, array $data = [])
    {
        if (empty($this->settings)) {
            return false;
        }

        $notifiable = new AnonymousNotifiable();

        if ($this->settings['slack'] ?? null) {
            $notifiable->route('slack', $this->settings['slack']['webhook']);
        }

        if ($this->settings['mail'] ?? null) {
            $notifiable->route('mail', $this->settings['mail']['address']);
        }

        $notification = new $notification($this->settings, $data);

        try {
            $notifiable->notifyNow($notification);
        } catch (\Exception $exception) {
            echo 'Unable to send notification: '.$exception->getMessage();

            return false;
        }

        return true;
    }
}
