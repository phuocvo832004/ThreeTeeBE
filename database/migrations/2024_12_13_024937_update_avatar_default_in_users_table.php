<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('avatar')
            ->update(['avatar' => 'https://res.cloudinary.com/dhhuv7n0h/image/upload/v1703303755/UserAvatar/avatar-default-icon_xigwu7.png']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')
                  ->default('https://res.cloudinary.com/dhhuv7n0h/image/upload/v1703303755/UserAvatar/avatar-default-icon_xigwu7.png') // Giá trị mặc định
                  ->change();
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->default(null)->change();
        });
    }
};
