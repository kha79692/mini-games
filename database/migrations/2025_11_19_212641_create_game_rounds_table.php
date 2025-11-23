<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_rounds', function (Blueprint $table) {
            $table->id();
            $table->string('session_code', 6);
            $table->integer('round_number');
            $table->string('player1_word')->nullable();
            $table->string('player2_word')->nullable();
            $table->boolean('player1_submitted')->default(false);
            $table->boolean('player2_submitted')->default(false);
            $table->timestamps();

            $table->unique(['session_code', 'round_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_rounds');
    }
};