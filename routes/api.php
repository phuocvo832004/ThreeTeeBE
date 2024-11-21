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
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ImageController;

require __DIR__.'/auth.php';

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::apiResource('products', ProductController::class);
Route::patch('products/{id}', [ProductController::class, 'patchUpdateProduct']); 
// Route::apiResource('orders',OrderController::class)->only([
//   'index','show','store','update'
// ]);

Route::apiResource('orders', OrderController::class);

Route::middleware(['auth:sanctum'])->group(function(){
    Route::apiResource('designs', DesignController::class);
});

//Route::apiResource('designs', DesignController::class);

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware(['auth:sanctum'])->group(function(){
    Route::apiResource('images', ImageController::class);
});

Route::middleware(['auth:sanctum'])->group(function(){
    Route::apiResource('reviews', ReviewController::class);
});

