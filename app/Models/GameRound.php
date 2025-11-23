<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameRound extends Model
{
    protected $fillable = [
        'session_code',
        'round_number',
        'player1_word',
        'player2_word',
        'player1_submitted',
        'player2_submitted'
    ];

    protected $casts = [
        'player1_submitted' => 'boolean',
        'player2_submitted' => 'boolean'
    ];
}