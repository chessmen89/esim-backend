<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // 1) نملأ أولاً أي قيم null
        DB::table('trips')
            ->whereNull('description')
            ->update(['description' => '']);

        // 2) ثم نغيّر العمود ليصبح NOT NULL
        Schema::table('trips', function (Blueprint $table) {
            $table->text('description')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('trips', function (Blueprint $table) {
            $table->text('description')->nullable()->change();
        });
    }
};
