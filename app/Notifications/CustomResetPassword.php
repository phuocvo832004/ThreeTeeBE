<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;

class CustomResetPassword extends BaseResetPassword
{
    public function toMail($notifiable)
    {
        $url = url(config('app.frontend_url')."/password-reset/{$this->token}?email={$notifiable->email}");

        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->view('emails.reset_password', ['notifiable' => $notifiable, 'url' => $url])
            ->subject('Yêu cầu đặt lại mật khẩu');
    }
}
