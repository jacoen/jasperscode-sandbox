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
            $table->string('password_token')->nullable()->after('remember_token');
            $table->timestamp('password_changed_at')->nullable()->after('password_token');
            $table->timestamp('token_expires_at')->nullable()->after('password_changed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('password_token');
            $table->dropColumn('password_changed_at');
            $table->dropColumn('token_expires_at');
        });
    }
};
