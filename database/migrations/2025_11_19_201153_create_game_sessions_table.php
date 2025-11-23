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
        Schema::create('game_sessions', function (Blueprint $table) {
        $table->id();
        $table->string('code', 6)->unique(); // 6-character unique code
        $table->string('game_type')->default('word_sync'); // which game
        $table->string('mode')->default('classic'); // game mode
        $table->enum('status', ['waiting', 'in_progress', 'completed'])->default('waiting');
        $table->integer('current_round')->default(0);
        $table->integer('max_players')->default(2);
        $table->string('player1_id')->nullable();
        $table->string('player2_id')->nullable();
        $table->string('winning_word')->nullable();
        $table->integer('rounds_taken')->nullable();
        $table->timestamps();
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_sessions');
    }
};
