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
use App\Models\OrderDetail;

require __DIR__.'/auth.php';

// public
Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{id}', [ProductController::class, 'show']);
Route::get('reviews/{product_id}', [ReviewController::class, 'index']);
Route::get('/product-details', [ProductDetailController::class, 'index']);
Route::get('/orders/{order}/payment-cancel', [OrderController::class, 'paymentCancel'])->name('orders.payment.cancel');
Route::get('/orders/{order}/payment-return', [OrderController::class, 'paymentReturn'])->name('orders.payment.return');
Route::post('/orders/{order}/payment-callback', [OrderController::class, 'handlePaymentCallback'])->name('orders.payment.callback');


// user
Route::middleware(['auth:sanctum'])->group(function () {
    // User profile
    Route::patch('/update-password', [UserController::class, 'updatePassword']);
    Route::post('/update-user', [UserController::class, 'updateUser']);
    Route::get('/user', [UserController::class, 'currentUser']);

    // Orders and order details
    Route::apiResource('orders', OrderController::class);
    //Route::apiResource('order_details', OrderDetailController::class);
    Route::post('/order_details', [OrderDetailController::class,'store']);
    Route::put('/orders/{order_id}/orderdetails/{product_detail_id}', [OrderDetailController::class, 'update']);
    Route::delete('/orders/{order_id}/orderdetails/{product_detail_id}', [OrderDetailController::class, 'destroy']);
    Route::get('/order/{order_id}/details', [OrderDetailController::class, 'index']);

    // Cart Management
    Route::post('/carts', [CartController::class, 'store']);
    Route::get('/carts', [CartController::class, 'index']);
    Route::get('/carts_5', [CartController::class, 'index5']);
    Route::patch('/carts/{product_detail_id}', [CartController::class, 'update']);
    Route::delete('/carts/{product_detail_id}', [CartController::class, 'destroy']);

    // Payment
    Route::post('/orders/{order}/payment-link', [OrderController::class, 'createPaymentLink']);
    Route::get('/orders/{order}/payment-info', [OrderController::class, 'getPaymentInfo']);
    Route::post('/orders/{order}/cancel-payment-link', [OrderController::class, 'cancelPaymentLink']);

    // Design Management
    Route::apiResource('designs', DesignController::class);

    // Images
    Route::apiResource('images', ImageController::class);
    Route::get('/products/{productId}/images', [ImageController::class, 'getImagesByProduct']);

    // Reviews
    Route::post('reviews', [ReviewController::class, 'store']);

    // Order&Detail
    Route::get('/orderanddetail/{orderId}',[OrderController::class],'getOrderUser');
});


// staff
Route::prefix('staff')->name('staff.')->middleware(['auth:sanctum', 'is_admin_or_staff'])->group(function () {
    // Product Management
    Route::apiResource('products', ProductController::class)->except(['edit', 'create']);
    Route::patch('products/{id}', [ProductController::class, 'patchUpdateProduct']);

    // Product Detail Management
    Route::prefix('product-details')->group(function () {
        Route::post('/', [ProductDetailController::class, 'store']);
        Route::get('/{id}', [ProductDetailController::class, 'show']);
        Route::put('/{id}', [ProductDetailController::class, 'update']);
        Route::delete('/{id}', [ProductDetailController::class, 'destroy']);
        Route::patch('/{id}', [ProductDetailController::class, 'patchUpdate']);
    });

    // User and Order Info (Read-only for staff)
    Route::get('/users/{userId}', [UserController::class, 'show']);
    Route::get('/user/{usersId}/orders', [UserController::class, 'showUserOrders']);
    Route::get('/order/{orderId}/details', [OrderDetailController::class, 'orderDetail']);


    // User Register Statistics
    Route::get('/user-statistics', [UserController::class, 'getUserStatistics']);
    
    // Order Statistics
    Route::get('/order-statistics', [OrderController::class, 'getOrderStatistics']);

    //Profit
    Route::get('/profit-statistics', [OrderController::class, 'getProfitStatistics']);
    
    //Product revenue
    Route::get('/product-revenue', [ProductController::class, 'getProductRevenue']);

    Route::get('/order/{orderId}/allinfo',[OrderController::class,'getOrderAdmin']);

});


// admin
Route::prefix('admin')->name('admin.')->middleware(['auth:sanctum', 'is_admin'])->group(function () {
    // User Management
    Route::get('/users/all', [UserController::class, 'getAllUsers']);
    Route::patch('users/{id}/role', [UserController::class, 'updateRole']);

    // Order Management
    Route::get('/orders/all', [OrderController::class, 'getAllOrders']);

    //User Log
    Route::get('/user-logs', [UserController::class, 'userLog']);
    

    Route::get('/dashboard-statistics', [UserController::class, 'getDashboardStatistics']);

    Route::get('/product-revenue-top3', [ProductController::class, 'getTop3ProductRevenuePerMonth']);

});
