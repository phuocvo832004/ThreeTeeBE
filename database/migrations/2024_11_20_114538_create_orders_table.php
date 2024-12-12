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
            $table->id('id');
            $table->foreignId("user_id")->constrained()->onDelete('cascade'); 
            $table->string('phonenumber'); 
            $table->text('address'); 
            $table->timestamp('order_date')->useCurrent(); 
            $table->integer('totalprice'); 
            $table->enum('status', ['pending', 'cancelled', 'delivery', 'success'])->default('pending'); 
            $table->date('payment_date')->nullable(); 
            $table->enum('payment_status', ['unpaid', 'paid', 'cancelled'])->default('unpaid'); 
            $table->string('payment_link')->nullable(); 
            $table->string('payment_link_id')->nullable();
            $table->timestamps(); 
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
