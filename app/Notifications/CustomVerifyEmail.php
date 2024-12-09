<?php

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Auth\Notifications\VerifyEmail as BaseVerifyEmail;
use Illuminate\Support\Facades\URL;

class CustomVerifyEmail extends BaseVerifyEmail
{
    protected function verificationUrl($notifiable)
    {
        // Tạo thời gian hết hạn cho URL
        $expiresAt = Carbon::now()->addMinutes(60); // Hết hạn sau 60 phút

        // Tạo URL có chữ ký tạm thời
        return URL::temporarySignedRoute(
            'verification.verify', // Route xác minh email
            $expiresAt, // Thời gian hết hạn
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())]
        );
    }

    public function toMail($notifiable)
    {
        $url = $this->verificationUrl($notifiable);

        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->view('emails.verify_email', ['notifiable' => $notifiable, 'url' => $url])
            ->subject('Xác minh email của bạn tại ThreeTee');
    }
}
