<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameSession extends Model
{
    protected $fillable = [
        'code',
        'game_type',
        'mode',
        'status',
        'current_round',
        'max_players',
        'player1_id',
        'player1_name',
        'player2_id',
        'player2_name',
        'winning_word',
        'rounds_taken',
        'round_started_at',
        'round_duration'
    ];

    protected $casts = [
        'round_started_at' => 'datetime'
    ];

    // Check if session is full
    public function isFull()
    {
        return $this->player1_id && $this->player2_id;
    }

    // Check if player is in this session
    public function hasPlayer($playerId)
    {
        return $this->player1_id === $playerId || $this->player2_id === $playerId;
    }
}
