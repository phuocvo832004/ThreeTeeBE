<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id('id'); // Tạo cột OrderId kiểu int và là khóa chính
            $table->foreignId("user_id");
            $table->string('phonenumber'); // Số điện thoại kiểu varchar
            $table->text('address'); // Địa chỉ kiểu text
            $table->timestamp('order_date')->useCurrent(); // Lấy giá trị thời gian hiện tại mặc định
            $table->integer('totalprice'); // Tổng giá kiểu int
            $table->enum('status', ['pending', 'cancelled', 'delivery', 'success'])->default('pending'); // Trạng thái đơn hàng
            $table->date('payment_date')->nullable(); // Ngày thanh toán (có thể null)
            $table->enum('payment_status', ['unpaid', 'paid', 'cancelled'])->default('unpaid'); // Trạng thái thanh toán
            $table->timestamps(); // Cột created_at và updated_at tự động

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
