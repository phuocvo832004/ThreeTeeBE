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
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['product_id']);
            $table->dropColumn('product_id'); 

            $table->foreignId('product_detail_id')->constrained('product_details')->onDelete('cascade');
        });
    }

public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            $table->dropForeign(['product_detail_id']);
            $table->dropColumn('product_detail_id');
        });
    }

};
