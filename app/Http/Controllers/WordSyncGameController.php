<?php

namespace App\Http\Controllers;

use App\Models\GameSession;
use App\Models\GameRound;
use Illuminate\Http\Request;

class WordSyncGameController extends Controller
{
    // Submit a word
    public function submitWord(Request $request)
    {
        $request->validate([
            'code' => 'required|string|size:6',
            'word' => 'required|string|max:30'
        ]);

        $code = strtoupper($request->input('code'));
        $word = trim($request->input('word'));
        $playerId = session()->getId();

        $session = GameSession::where('code', $code)->first();

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Game not found'], 404);
        }

        // Determine which player this is
        $playerNumber = null;
        if ($session->player1_id === $playerId) {
            $playerNumber = 1;
        } elseif ($session->player2_id === $playerId) {
            $playerNumber = 2;
        } else {
            return response()->json(['success' => false, 'message' => 'Not in this game'], 403);
        }

        // Get or create round record
        $round = GameRound::firstOrCreate(
            [
                'session_code' => $code,
                'round_number' => $session->current_round
            ],
            [
                'player1_word' => null,
                'player2_word' => null,
                'player1_submitted' => false,
                'player2_submitted' => false
            ]
        );

        // Update with player's word
        if ($playerNumber === 1) {
            $round->update([
                'player1_word' => $word,
                'player1_submitted' => true
            ]);
        } else {
            $round->update([
                'player2_word' => $word,
                'player2_submitted' => true
            ]);
        }

        // Refresh to get latest data
        $round->refresh();

        // Check if both players submitted
        if ($round->player1_submitted && $round->player2_submitted) {
            $word1 = strtolower(trim($round->player1_word));
            $word2 = strtolower(trim($round->player2_word));

            // Check if words match AND are not empty/placeholder
            $matched = ($word1 === $word2) &&
                       ($word1 !== '') &&
                       ($word1 !== '[no word]');

            return response()->json([
                'success' => true,
                'both_submitted' => true,
                'words' => [
                    'player1' => $round->player1_word,
                    'player2' => $round->player2_word
                ],
                'matched' => $matched
            ]);
        }
        return response()->json([
            'success' => true,
            'both_submitted' => false,
            'message' => 'Waiting for other player...'
        ]);
    }

    // Check round status (for polling)
    public function checkRound(Request $request)
    {
        $code = strtoupper($request->input('code'));
        $session = GameSession::where('code', $code)->first();

        if (!$session) {
            return response()->json(['success' => false], 404);
        }

        $round = GameRound::where('session_code', $code)
            ->where('round_number', $session->current_round)
            ->first();

        if (!$round) {
            return response()->json([
                'success' => true,
                'both_submitted' => false
            ]);
        }

        if ($round->player1_submitted && $round->player2_submitted) {
            $word1 = strtolower(trim($round->player1_word));
            $word2 = strtolower(trim($round->player2_word));

            // Check if words match AND are not empty/placeholder
            $matched = ($word1 === $word2) &&
                       ($word1 !== '') &&
                       ($word1 !== '[no word]');

            return response()->json([
                'success' => true,
                'both_submitted' => true,
                'words' => [
                    'player1' => $round->player1_word,
                    'player2' => $round->player2_word
                ],
                'matched' => $matched,
                'current_round' => $session->current_round
            ]);
        }

        return response()->json([
            'success' => true,
            'both_submitted' => false
        ]);
    }

    // Start next round
    // Start next round
public function nextRound(Request $request)
{
    $code = strtoupper($request->input('code'));
    $playerId = session()->getId();

    $session = GameSession::where('code', $code)->first();

    if (!$session) {
        return response()->json(['success' => false], 404);
    }

    // Only host can start next round
    if ($session->player1_id !== $playerId) {
        return response()->json([
            'success' => false,
            'message' => 'Only host can advance rounds'
        ], 403);
    }

    // Increment round and reset timer
    $session->increment('current_round');
    $session->update([
        'round_started_at' => now()
    ]);

    return response()->json([
        'success' => true,
        'current_round' => $session->current_round
    ]);
}

    // Mark game as completed
    public function completeGame(Request $request)
    {
        $code = strtoupper($request->input('code'));
        $winningWord = $request->input('winning_word');

        $session = GameSession::where('code', $code)->first();

        if (!$session) {
            return response()->json(['success' => false], 404);
        }

        $session->update([
            'status' => 'completed',
            'winning_word' => $winningWord,
            'rounds_taken' => $session->current_round
        ]);

        return response()->json([
            'success' => true,
            'rounds' => $session->current_round
        ]);
    }

    // Get current game state (timer, round, etc)
    public function getGameState(Request $request)
    {
        $code = strtoupper($request->input('code'));
        $session = GameSession::where('code', $code)->first();

        if (!$session) {
            return response()->json(['success' => false, 'message' => 'Session not found'], 404);
        }

        // Check if round_started_at is null
        if (!$session->round_started_at) {
            // Fix it by setting it now
            $session->update(['round_started_at' => now()]);
            $session->refresh();
        }

        // Calculate time remaining - FIX HERE
        $roundStarted = $session->round_started_at;
        $now = now();
        $timeElapsed = $now->diffInSeconds($roundStarted, false); // false = don't make absolute

        // If timeElapsed is negative, round just started
        if ($timeElapsed < 0) {
            $timeElapsed = 0;
        }

        $timeRemaining = max(0, $session->round_duration - $timeElapsed);
        $timeRemaining = (int) $timeRemaining; // Convert to integer (no decimals)

        // Get previous round words
        $previousRound = null;
        if ($session->current_round > 1) {
            $prevRoundData = GameRound::where('session_code', $code)
                ->where('round_number', $session->current_round - 1)
                ->first();

            if ($prevRoundData) {
                $previousRound = [
                    'player1_word' => $prevRoundData->player1_word,
                    'player2_word' => $prevRoundData->player2_word
                ];
            }
        }

        return response()->json([
            'success' => true,
            'current_round' => $session->current_round,
            'time_remaining' => $timeRemaining,
            'status' => $session->status,
            'previous_round' => $previousRound,
            'player1_name' => $session->player1_name,
            'player2_name' => $session->player2_name
        ]);
    }
}