<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountActivatedNotification extends Notification implements ShouldQueue
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
            ->subject('Account activated')
            ->greeting('Dear '.$notifiable->name)
            ->line('Via this message we notify you that your account has been activated.')
            ->line('You can login now.')
            ->action('Login', route('login'))
            ->line('If you did not activate this account please contact us.')
            ->line('Thank you for using our application!');
    }
}
