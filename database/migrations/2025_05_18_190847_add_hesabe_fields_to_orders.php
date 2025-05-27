<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   public function up()
{
    Schema::table('orders', function (Blueprint $table) {
        $table->string('transaction_id')->nullable()->after('payment_data');
        $table->unsignedBigInteger('hesabe_id')->nullable()->after('transaction_id');
        $table->string('payment_id')->nullable()->after('hesabe_id');
        $table->string('terminal')->nullable()->after('payment_id');
        $table->string('track_id')->nullable()->after('terminal');
        $table->string('payment_type')->nullable()->after('track_id');
        $table->string('service_type')->nullable()->after('payment_type');
    });
}
};
