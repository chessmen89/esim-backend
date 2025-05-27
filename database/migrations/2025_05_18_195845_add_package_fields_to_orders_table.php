<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('package_id')->nullable()->after('user_id');
            $table->integer('quantity')->default(1)->after('package_id');
            $table->string('type')->default('sim')->after('quantity');
            // (You’ve already added amount & currency in a previous migration.)
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['package_id','quantity','type']);
        });
    }
};