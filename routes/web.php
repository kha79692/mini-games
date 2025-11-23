<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GameController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\WordSyncGameController;

Route::get('/', function () {
    return redirect('/menu');
});

Route::get('/menu', [GameController::class, 'menu']);
Route::get('/game/word-sync', [GameController::class, 'wordSync']);


// Session routes
Route::post('/session/create', [SessionController::class, 'create']);
Route::post('/session/join', [SessionController::class, 'join']);
Route::get('/game/lobby/{code}', [SessionController::class, 'lobby']);

Route::post('/session/start', [SessionController::class, 'start']);
Route::get('/session/status/{code}', [SessionController::class, 'status']);

Route::get('/game/play/{code}', [SessionController::class, 'play']);
Route::post('/session/leave', [SessionController::class, 'leave']);

Route::post('/game/submit-word', [WordSyncGameController::class, 'submitWord']);
Route::post('/game/check-round', [WordSyncGameController::class, 'checkRound']);
Route::post('/game/next-round', [WordSyncGameController::class, 'nextRound']);
Route::post('/game/complete', [WordSyncGameController::class, 'completeGame']);

Route::get('/session/lobby-status/{code}', [SessionController::class, 'lobbyStatus']);
Route::post('/game/state', [WordSyncGameController::class, 'getGameState']);