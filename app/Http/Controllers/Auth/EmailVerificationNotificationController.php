<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmailVerificationNotificationController extends Controller
{
    public function store(Request $request)
    {
        // Validate email
        $request->validate([
            'email' => 'required|email',
        ]);

        // Tìm người dùng qua email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json([
                'message' => 'Không tìm thấy người dùng với email này.',
            ], 404);
        }

        // Kiểm tra xem email đã được xác thực chưa
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email đã được xác thực.',
            ], 400);
        }

        // Gửi email xác minh
        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Email xác minh đã được gửi lại.',
        ]);
    }

}
