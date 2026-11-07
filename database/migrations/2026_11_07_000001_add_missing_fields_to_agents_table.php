<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->string('phone', 30)->after('email');
            $table->decimal('wallet', 12, 2)->default(0.00)->after('password');
        });

        Schema::table('agents', function (Blueprint $table) {
            $table->string('mobile', 30)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('agents', function (Blueprint $table) {
            $table->dropColumn(['phone', 'wallet']);
            $table->string('mobile', 255)->nullable(false)->change();
        });
    }
};
