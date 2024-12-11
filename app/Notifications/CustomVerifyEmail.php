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

        // Tạo URL với tham số id, hash, và expires
        return config('app.frontend_url') . "/verify-email?id=" . $notifiable->getKey() .
            "&hash=" . sha1($notifiable->getEmailForVerification()) .
            "&expires=" . $expiresAt->timestamp; // Thêm thời gian hết hạn vào URL
    }

    public function toMail($notifiable)
    {
        // Tạo URL xác minh
        $url = $this->verificationUrl($notifiable);
    
        return (new \Illuminate\Notifications\Messages\MailMessage)
            ->view('emails.verify_email', [
                'notifiable' => $notifiable,
                'url' => $url,
            ])
            ->subject('Xác minh email của bạn tại ThreeTee');
    }
}
