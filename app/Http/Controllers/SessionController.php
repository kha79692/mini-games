<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GameSession;
use Illuminate\Support\Str;

class SessionController extends Controller
{
    // Generate unique 6-character code
    private function generateCode()
    {
        do {
            $code = strtoupper(Str::random(6));
        } while (GameSession::where('code', $code)->exists());

        return $code;
    }

    public function create(Request $request)
    {
        $code = $this->generateCode();

        // Generate a temporary player ID (we'll add proper auth later)
        $playerId = session()->getId();

        $session = GameSession::create([
            'code' => $code,
            'game_type' => 'word_sync',
            'mode' => $request->input('mode', 'classic'),
            'status' => 'waiting',
            'player1_id' => $playerId,
            'player1_name' => $request->input('username')
        ]);

        return response()->json([
            'success' => true,
            'code' => $code,
            'session_id' => $session->id
        ]);
    }

    public function join(Request $request)
    {
    // Validate
    $request->validate([
        'code' => 'required|string|size:6',
        'username' => 'required|string|max:20|min:2'
    ]);

    $code = strtoupper($request->input('code'));
    $playerId = session()->getId();

    $session = GameSession::where('code', $code)->first();

    if (!$session) {
        return response()->json([
            'success' => false,
            'message' => 'Game code not found!'
        ], 404);
    }

    if ($session->status !== 'waiting') {
        return response()->json([
            'success' => false,
            'message' => 'This game has already started!'
        ], 400);
    }

    if ($session->isFull()) {
        return response()->json([
            'success' => false,
            'message' => 'This game is full!'
        ], 400);
    }

    // Add player 2 with name
    $session->update([
        'player2_id' => $playerId,
        'player2_name' => $request->input('username'),  // Add name
        'status' => 'in_progress'
    ]);

    return response()->json([
        'success' => true,
        'code' => $code,
        'session_id' => $session->id
    ]);
    }
    public function lobby($code)
    {
        $session = GameSession::where('code', $code)->firstOrFail();

        return view('games.word-sync-lobby', [
            'session' => $session
        ]);
    }
    // Add this method
    public function start(Request $request)
{
    $request->validate([
        'code' => 'required|string|size:6'
    ]);

    $code = strtoupper($request->input('code'));
    $playerId = session()->getId();

    $session = GameSession::where('code', $code)->first();

    if (!$session) {
        return response()->json([
            'success' => false,
            'message' => 'Game not found!'
        ], 404);
    }

    // Check if player is the host (player 1)
    if ($session->player1_id !== $playerId) {
        return response()->json([
            'success' => false,
            'message' => 'Only the host can start the game!'
        ], 403);
    }

    // Check if both players are present
    if (!$session->player2_id) {
        return response()->json([
            'success' => false,
            'message' => 'Waiting for another player!'
        ], 400);
    }

    // Update session status and start timer
    $session->update([
        'status' => 'playing',
        'current_round' => 1,
        'round_started_at' => now(),
        'round_duration' => 20
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Game started!'
    ]);
}

// Add this method to check game status (for player 2)
public function status($code)
{
    $session = GameSession::where('code', strtoupper($code))->first();

    if (!$session) {
        return response()->json([
            'success' => false,
            'message' => 'Game not found'
        ], 404);
    }

    return response()->json([
        'success' => true,
        'status' => $session->status,
        'current_round' => $session->current_round
    ]);
}

// Show the actual game play page
public function play($code)
{
    $session = GameSession::where('code', strtoupper($code))->firstOrFail();

    // Make sure game has started
    if ($session->status !== 'playing') {
        return redirect('/game/lobby/' . $code);
    }

    return view('games.word-sync-play', [
        'session' => $session
    ]);
}

public function leave(Request $request)
{
    $request->validate([
        'code' => 'required|string|size:6'
    ]);

    $code = strtoupper($request->input('code'));
    $playerId = session()->getId();

    $session = GameSession::where('code', $code)->first();

    if (!$session) {
        return response()->json([
            'success' => false,
            'message' => 'Game not found!'
        ], 404);
    }

    // If host (player 1) leaves, delete the entire session
    if ($session->player1_id === $playerId) {
        $session->delete();
        return response()->json([
            'success' => true,
            'message' => 'Game ended by host'
        ]);
    }

    // If player 2 leaves, just remove them and reset status
    if ($session->player2_id === $playerId) {
        $session->update([
            'player2_id' => null,
            'player2_name' => null,
            'status' => 'waiting'
        ]);
        return response()->json([
            'success' => true,
            'message' => 'Left the game'
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'You are not in this game'
    ], 403);
}
// Check lobby status (for real-time updates)
public function lobbyStatus($code)
{
    $session = GameSession::where('code', strtoupper($code))->first();

    if (!$session) {
        return response()->json([
            'exists' => false
        ]);
    }

    return response()->json([
        'exists' => true,
        'status' => $session->status,
        'player1_id' => $session->player1_id,
        'player2_id' => $session->player2_id,
        'player1_name' => $session->player1_name,
        'player2_name' => $session->player2_name
    ]);
}
}
