<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductDetailController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\DesignController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\OrderDetailController;
use App\Http\Controllers\UserController;

require __DIR__.'/auth.php';
Route::middleware('auth:sanctum')->patch('/update-password', [UserController::class, 'updatePassword']);
Route::middleware('auth:sanctum')->patch('/update-user', [UserController::class, 'updateUser']);

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

    Route::put('/orders/{order_id}/orderdetails/{product_detail_id}', [OrderDetailController::class, 'update']);
    Route::delete('/orders/{order_id}/orderdetails/{product_detail_id}', [OrderDetailController::class, 'destroy']);

    Route::get('/order/{order_id}/details', [OrderDetailController::class, 'index']);


    Route::post('/carts',[CartController::class,'store']);
    Route::get('/carts',[CartController::class,'index']);
    Route::get('/carts_5',[CartController::class,'index5']);
    Route::patch('/carts/{product_detail_id}', [CartController::class, 'update']);
    Route::delete('/carts/{product_detail_id}', [CartController::class, 'destroy']);


    Route::post('/orders/{order}/payment-link', [OrderController::class, 'createPaymentLink']);
    Route::get('/orders/{order}/payment-info', [OrderController::class, 'getPaymentInfo']);  
    Route::post('/orders/{order}/payment-callback', [OrderController::class, 'handlePaymentCallback'])->name('orders.payment.callback');
    Route::get('/orders/{order}/payment-return', [OrderController::class, 'paymentReturn'])->name('orders.payment.return');
    Route::post('/orders/{order}/cancel-payment-link', [OrderController::class, 'cancelPaymentLink']);

});
Route::get('/orders/{order}/payment-cancel', [OrderController::class, 'paymentCancel'])->name('orders.payment.cancel'); 

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
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::middleware(['auth:sanctum'])->group(function() {
    Route::patch('products/{id}', [ProductController::class, 'patchUpdateProduct']);
    Route::put('products/{id}', [ProductController::class, 'update']);
    Route::post('products', [ProductController::class, 'store']); 
    Route::delete('products/{id}', [ProductController::class, 'destroy']); 

    Route::prefix('product-details')->group(function () {
        Route::get('/', [ProductDetailController::class, 'index']); 
        Route::post('/', [ProductDetailController::class, 'store']); 
        Route::get('/{id}', [ProductDetailController::class, 'show']); 
        Route::put('/{id}', [ProductDetailController::class, 'update']); 
        Route::delete('/{id}', [ProductDetailController::class, 'destroy']);
    });
});

Route::get('reviews/{product_id}', [ReviewController::class, 'index']); 
Route::middleware(['auth:sanctum'])->group(function(){
    Route::post('reviews', [ReviewController::class, 'store']); 


});

Route::middleware(['auth:sanctum'])->group(function(){
    Route::apiResource('images', ImageController::class);
    Route::get('/products/{productId}/images', [ImageController::class, 'getImagesByProduct']);
});


Route::prefix('admin')->name('admin.')->middleware(['auth:sanctum', 'is_admin'])->group(function () {
    Route::get('/orders/all', [OrderController::class, 'getAllOrders']);

    Route::get('/users/all', [UserController::class, 'getAllUsers']);

    Route::patch('users/{id}/role', [UserController::class, 'updateRole']);

    //Giong staff
    Route::get('/users/{userId}',[UserController::class, 'show']);
    Route::get('/user/{usersId}/orders',[UserController::class, 'showUserOrders']);
    Route::get('/order/{orderId}/details',[OrderDetailController::class,'orderDetail']);
});
Route::prefix('staff')->name('staff.')->middleware(['auth:sanctum', 'is_admin_or_staff'])->group(function () {
    Route::get('/users/{userId}',[UserController::class, 'show']);
    Route::get('/user/{usersId}/orders',[UserController::class, 'showUserOrders']);
    Route::get('/order/{orderId}/details',[OrderDetailController::class,'orderDetail']);
});