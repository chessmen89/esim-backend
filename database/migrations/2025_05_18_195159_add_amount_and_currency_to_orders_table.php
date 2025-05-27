<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
{
    Schema::table('orders', function (Blueprint $table) {
        // store the KWD amount (3 decimal places)
        $table->decimal('amount', 8, 3)->nullable()->after('status');
        // store the 3-letter currency code
        $table->string('currency', 3)->nullable()->after('amount');
    });
}

public function down()
{
    Schema::table('orders', function (Blueprint $table) {
        $table->dropColumn(['amount','currency']);
    });
}
};
