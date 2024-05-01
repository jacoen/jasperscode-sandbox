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
            $table->dateTime('locked_until')->nullable();
            $table->unsignedInteger('two_factor_attempts')->default(0);
            $table->dateTime('last_attempt_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locked_until');
            $table->dropColumn('two_factor_attempts');
            $table->dropColumn('last_attempt_at');
        });
    }
};
