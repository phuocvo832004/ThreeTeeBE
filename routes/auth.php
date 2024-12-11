<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::post('/register', [RegisteredUserController::class, 'store'])
    ->middleware('guest')
    ->name('register');

Route::post('/login', [LoginController::class, 'store'])
    ->middleware('guest')
    ->name('login');

Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])
    ->middleware('guest')
    ->name('password.email');

Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware('guest')
    ->name('password.store');

// Route::get('/verify-email/{id}/{hash}', VerifyEmailController::class)
//     ->middleware(['auth:sanctum', 'signed', 'throttle:6,1'])
//     ->name('verification.verify');
    

Route::get('verify-email', function (Request $request) {
    $id = $request->query('id');
    $hash = $request->query('hash');
    $expires = $request->query('expires');

    if (!$id || !$hash || !$expires) {
        return response()->json(['message' => 'Invalid verification link'], 400);
    }

    if (now()->timestamp > $expires) {
        return response()->json(['message' => 'Verification link expired'], 400);
    }

    $user = User::find($id);
    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    $expectedHash = sha1($user->getEmailForVerification());
    if (!hash_equals($expectedHash, $hash)) {
        return response()->json(['message' => 'Invalid verification link'], 400);
    }

    if ($user->hasVerifiedEmail()) {
        return response()->json(['message' => 'Email is already verified'], 200);
    }

    $user->markEmailAsVerified();
    return response()->json(['message' => 'Email verified successfully'], 200);
})->name('verification.verify');


Route::post('/email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
    ->middleware(['guest', 'throttle:6,1']) 
    ->name('verification.send');


Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth:sanctum')
    ->name('logout');
