<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTripsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * إنشاء جدول trips مع الحقول المطلوبة.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم الرحلة - مطلوب
            $table->string('pdf_path'); // مسار ملف PDF للرحلة - مطلوب
            $table->string('image_path'); // مسار صورة الرحلة - مطلوب
            $table->string('country_code', 10); // كود الدولة - مطلوب
            $table->decimal('price', 8, 2); // سعر الرحلة - مطلوب (8 أرقام إجمالية و2 بعد الفاصلة)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * حذف جدول trips.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('trips');
    }
}
