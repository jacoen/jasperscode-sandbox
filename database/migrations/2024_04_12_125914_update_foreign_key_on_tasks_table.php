<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // maken van een tijdelijke variabele voor de gegevens
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('temp_user_id')->nullable()->after('user_id');
        });

        // kopiëren van de gegevens uit de user_id kolom
        DB::statement('UPDATE tasks SET temp_user_id = user_id');

        // verwijderen van de user_id kolom
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        // nieuwe user_id kolom en deze instellen als foreign key
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('author_id');

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });

        // kopiëren van de gegevens van de tijdelijke kolom naar de nieuwe user_id kolom
        DB::statement('UPDATE tasks SET user_id = temp_user_id');

        // verwijderen tijdelijke kolom
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('temp_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->unsignedBigInteger('temp_user_id')->nullable();
        });

        DB::statement('UPDATE tasks SET temp_user_id = user_id');

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained();
        });

        DB::statement('UPDATE tasks SET user_id = temp_user_id');

        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('temp_user_id');
        });
    }
};
