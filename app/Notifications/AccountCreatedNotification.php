<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountCreatedNotification extends Notification implements ShouldQueue
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
            ->subject('Your account has been created')
            ->greeting('Dear '.$notifiable->name)
            ->line('Via this message we want to notify you that your account has been created.')
            ->line('Now you need to activate your account, you can activate your account by creating a password.')
            ->line('You can click the button below to add your password')
            ->action('Notification Action', route('activate-account.create', $notifiable->password_token))
            ->line('Thank you for using our application!');
    }
}
