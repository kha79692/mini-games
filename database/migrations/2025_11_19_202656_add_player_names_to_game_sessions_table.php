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
        Schema::table('game_sessions', function (Blueprint $table) {
        $table->string('player1_name')->nullable()->after('player1_id');
        $table->string('player2_name')->nullable()->after('player2_id');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->dropColumn(['player1_name', 'player2_name']);
        });
    }
};
