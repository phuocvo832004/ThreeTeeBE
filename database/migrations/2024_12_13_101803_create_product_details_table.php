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
        Schema::create('product_details', function (Blueprint $table) {
            $table->id(); 
            $table->foreignId('product_id')
                  ->constrained('products')
                  ->onDelete('cascade')
                  ->onUpdate('cascade');
            $table->integer('stock')->default(0); 
            $table->decimal('price', 10, 2); 
            $table->string('size', 50); 
            $table->timestamps(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_details');
    }
};
