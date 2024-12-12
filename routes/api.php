<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DesignController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\OrderDetailController;
use App\Http\Controllers\UserController;

require __DIR__.'/auth.php';
Route::middleware('auth:sanctum')->patch('/update-password', [UserController::class, 'updatePassword']);

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

 
// Route::apiResource('orders',OrderController::class)->only([
//   'index','show','store','update'
// ]);

//Route::apiResource('orders', OrderController::class);


Route::middleware(['auth:sanctum'])->group(function() {
    Route::apiResource('orders', OrderController::class);
    Route::apiResource('order_details', OrderDetailController::class);

    Route::put('/orders/{order_id}/orderdetails/{orderDetail}', [OrderDetailController::class, 'update']);
    Route::get('/order/{order_id}/details', [OrderDetailController::class, 'index']);

    Route::get('/admin/orders/all', [OrderController::class, 'getAllOrders']);

    // Route::post('/carts',[CartController::class,'store']);
    // Route::get('/carts',[CartController::class,'index']);
    // API liên quan đến thanh toán
    Route::post('/orders/{order}/payment-link', [OrderController::class, 'createPaymentLink']); // Tạo link thanh toán
    Route::get('/orders/{order}/payment-info', [OrderController::class, 'getPaymentInfo']);   // Lấy thông tin thanh toán
    Route::post('/orders/{order}/payment-callback', [OrderController::class, 'handlePaymentCallback'])->name('orders.payment.callback'); // Xử lý callback thanh toán
    Route::post('/orders/{order}/cancel-payment-link', [OrderController::class, 'cancelPaymentLink']); // Hủy link thanh toán
    Route::get('/orders/{order}/payment-return', [OrderController::class, 'paymentReturn'])->name('orders.payment.return'); // Xử lý trả lại thanh toán
    Route::get('/orders/{order}/payment-cancel', [OrderController::class, 'paymentCancel'])->name('orders.payment.cancel'); // Xử lý hủy thanh toán
    


});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/designs', [DesignController::class, 'index']);
    Route::post('/designs', [DesignController::class, 'store']);
    Route::get('/designs/{id}', [DesignController::class, 'show']);
    Route::put('/designs/{id}', [DesignController::class, 'update']);
    Route::delete('/designs/{id}', [DesignController::class, 'destroy']);
});



Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/products', [ProductController::class, 'index']);

Route::middleware(['auth:sanctum'])->group(function() {
    Route::patch('products/{id}', [ProductController::class, 'patchUpdateProduct']);
    Route::put('products/{id}', [ProductController::class, 'update']);
    Route::post('products', [ProductController::class, 'store']); 
    Route::delete('products/{id}', [ProductController::class, 'destroy']); 
});


Route::middleware(['auth:sanctum'])->group(function(){
    Route::apiResource('reviews', ImageController::class);
});

Route::middleware(['auth:sanctum'])->group(function(){
    Route::apiResource('images', ImageController::class);
});


