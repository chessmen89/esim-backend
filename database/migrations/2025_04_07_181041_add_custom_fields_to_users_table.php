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
        Schema::table('users', function (Blueprint $table) {
            // Add the columns after the 'password' column (or adjust as needed)
            $table->date('date_of_birth')->after('password'); // Required date of birth
            $table->string('mobile_number')->nullable()->after('date_of_birth'); // Optional mobile number
            $table->string('country')->nullable()->after('mobile_number'); // Optional country
            $table->string('provider_id')->nullable()->after('country'); // Optional for social login
            $table->string('provider_name')->nullable()->after('provider_id'); // Optional for social login

            // Add index for faster lookup if needed later for social logins
            $table->index(['provider_name', 'provider_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        // Temporarily comment out the dropIndex line because it didn't exist before
        // $table->dropIndex(['provider_name', 'provider_id']); // Drop index first
        $table->dropColumn(['provider_name', 'provider_id', 'country', 'mobile_number', 'date_of_birth']);
    });
}
};
