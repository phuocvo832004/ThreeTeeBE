<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): JsonResponse
    {
        $request->authenticate();
    
        // Lấy thông tin người dùng từ email
        $user = User::where('email', $request->email)->first();
    
        // Kiểm tra xem email đã được xác minh chưa
        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email chưa được xác minh. Vui lòng xác minh email trước khi đăng nhập.',
            ], 403); // HTTP 403: Forbidden
        }
    
        // Tạo token và trả về thông tin người dùng
        $data = [
            'token' => $user->createToken("token for " . $user->email)->plainTextToken,
            'user' => $user
        ];
    
        return response()->json($data, 201); // HTTP 201: Created
    }
    

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
