<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
{
    Schema::table('game_sessions', function (Blueprint $table) {
        $table->timestamp('round_started_at')->nullable();
        $table->integer('round_duration')->default(20); // seconds
    });
}

public function down(): void
{
    Schema::table('game_sessions', function (Blueprint $table) {
        $table->dropColumn(['round_started_at', 'round_duration']);
    });
}
};
