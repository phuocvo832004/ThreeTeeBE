<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetLinkController extends Controller
{
    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        // Validate email input
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // Send the password reset link
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            if ($status == Password::INVALID_USER) {
                throw ValidationException::withMessages([
                    'email' => ['Email không tồn tại trong hệ thống.'],
                ]);
            }
            throw ValidationException::withMessages([
                'email' => [__('Có lỗi xảy ra. Vui lòng thử lại sau.')],
            ]);
        }

        return response()->json(['status' => 'Liên kết đặt lại mật khẩu đã được gửi đến email của bạn.']);
    }
}
