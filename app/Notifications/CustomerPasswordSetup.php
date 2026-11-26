<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerPasswordSetup extends Notification
{
    use Queueable;

    public function __construct(private string $token) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Set your Bali Scenic account password')
            ->line('Use this link to verify your email and set or reset your account password.')
            ->action('Set password', route('customer.password.form', ['token' => $this->token, 'email' => $notifiable->email]))
            ->line('This link expires in 60 minutes. If you did not request it, you can ignore this email.');
    }
}
