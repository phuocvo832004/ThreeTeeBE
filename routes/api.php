<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DesignController;


// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('login', [AuthController::class, 'login']);

Route::post('register', [AuthController::class, 'register']);
Route::post('forgot-password', [ForgotPasswordController::class, 'forgotPassword']);
Route::post('reset-password', [ForgotPasswordController::class, 'resetPassword']);
Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify']);


Route::apiResource('products', ProductController::class);
Route::patch('products/{id}', [ProductController::class, 'patchUpdateProduct']); 
// Route::apiResource('orders',OrderController::class)->only([
//   'index','show','store','update'
// ]);
Route::apiResource('orders', OrderController::class);


Route::apiResource('designs', DesignController::class);

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

require __DIR__.'/auth.php';