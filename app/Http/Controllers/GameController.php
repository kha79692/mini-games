<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GameController extends Controller
{
    // Show the game menu
    public function menu()
    {
        return view('menu');
    }

    // Show Word Sync game lobby
    public function wordSync()
    {
        return view('games.word-sync');
    }
}
