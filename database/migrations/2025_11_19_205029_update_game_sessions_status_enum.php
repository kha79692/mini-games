<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('game_sessions', function (Blueprint $table) {
        // Drop the old enum
        $table->dropColumn('status');
    });

    Schema::table('game_sessions', function (Blueprint $table) {
        // Add the new enum with 'playing' status
        $table->enum('status', ['waiting', 'in_progress', 'playing', 'completed'])->default('waiting');
    });
}

public function down(): void
{
    Schema::table('game_sessions', function (Blueprint $table) {
        $table->dropColumn('status');
    });

    Schema::table('game_sessions', function (Blueprint $table) {
        $table->enum('status', ['waiting', 'in_progress', 'completed'])->default('waiting');
    });
}
};
