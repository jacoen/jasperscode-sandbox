<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewTokenRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
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
        return (new MailMessage)
            ->subject('new token')
            ->greeting('Dear '.$notifiable->name.', ')
            ->line('Via this message we want send your the new token you have requested to activate your account.')
            ->line('This token will expire within an hour.')
            ->line('Click on the button below to activate your account by following the steps on the page.')
            ->action('Notification Action', route('activate-account.create', $notifiable->password_token))
            ->line('Thank you for using our application!');
    }
}
