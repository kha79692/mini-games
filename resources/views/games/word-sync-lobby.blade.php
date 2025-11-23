<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Lobby - {{ $session->code }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center">
    <div class="max-w-2xl mx-auto p-8 text-center">

        <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <a href="/menu" class="text-gray-400 hover:text-white">
                    ← Menu
                </a>
                <button id="leave-lobby-btn" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg text-sm">
                    🚪 Leave
                </button>
            </div>

            <h1 class="text-4xl font-bold text-center">🎮 Game Lobby</h1>
        </div>
        <!-- Game Code Display -->
        <div class="bg-gray-800 rounded-xl p-8 mb-8">
            <p class="text-gray-400 mb-2">Share this code with your friend:</p>
            <div class="text-6xl font-bold tracking-widest text-green-400 mb-4">
                {{ $session->code }}
            </div>
            <button onclick="copyCode()" class="bg-blue-600 hover:bg-blue-700 px-6 py-2 rounded-lg text-sm">
                📋 Copy Code
            </button>
        </div>

        <!-- Players Status -->
        <div class="bg-gray-800 rounded-xl p-8 mb-8">
            <h2 class="text-xl font-bold mb-4">Players</h2>
            <div class="space-y-3">
                <div class="flex items-center gap-3 bg-green-900 bg-opacity-30 p-4 rounded-lg border-2 border-green-600">
                    <span class="text-2xl">👤</span>
                    <span class="font-bold">{{ $session->player1_name }}</span>
                    <span class="ml-auto">
                        <span class="bg-yellow-600 text-xs px-2 py-1 rounded">HOST</span>
                        <span class="text-green-400 ml-2">✓ Ready</span>
                    </span>
                </div>
                <div class="flex items-center gap-3 bg-gray-700 p-4 rounded-lg @if($session->player2_id) border-2 border-green-600 @endif">
                    <span class="text-2xl">👤</span>
                    @if($session->player2_id)
                        <span class="font-bold">{{ $session->player2_name }}</span>
                        <span class="ml-auto text-green-400">✓ Ready</span>
                    @else
                        <span class="text-gray-400">Waiting for player...</span>
                        <span class="ml-auto text-yellow-400">⏳</span>
                    @endif
                </div>
            </div>
        </div>

        @php
            $currentPlayerId = session()->getId();
            $isHost = $session->player1_id === $currentPlayerId;
        @endphp

        @if($session->player2_id)
            @if($isHost)
                <!-- Only host sees this button -->
                <button id="start-btn" class="bg-green-600 hover:bg-green-700 px-12 py-4 rounded-lg font-bold text-xl transition transform hover:scale-105">
                    🚀 Start Game
                </button>
            @else
                <!-- Player 2 sees waiting message -->
                <p class="text-gray-400 text-lg">Waiting for <span class="text-yellow-400 font-bold">{{ $session->player1_name }}</span> to start the game...</p>
                <div class="mt-4">
                    <div class="animate-pulse text-4xl">⏳</div>
                </div>
            @endif
        @else
            <p class="text-gray-400">Waiting for another player to join...</p>
            <div class="mt-4">
                <div class="animate-pulse text-4xl">⏳</div>
            </div>
        @endif

    </div>

    <script>
        function copyCode() {
            navigator.clipboard.writeText('{{ $session->code }}');
            alert('Code copied to clipboard!');
        }

        @php
            $isHost = $session->player1_id === session()->getId();
        @endphp

        let checkInterval;

        // Check if game has started (for player 2)
        async function checkGameStarted() {
            try {
                const response = await fetch('/session/status/{{ $session->code }}');
                const data = await response.json();

                if (data.status === 'playing') {
                    clearInterval(checkInterval);
                    window.location.href = '/game/play/{{ $session->code }}';
                }
            } catch (error) {
                console.error('Error checking status:', error);
            }
        }

        // Check lobby status continuously
        async function checkLobbyStatus() {
            try {
                const response = await fetch('/session/lobby-status/{{ $session->code }}');
                const data = await response.json();

                if (!data.exists) {
                    // Game was deleted (host left)
                    alert('The host has left. Returning to menu...');
                    clearInterval(checkInterval);
                    window.location.href = '/menu';
                    return;
                }

                // Check if game started
                if (data.status === 'playing') {
                    clearInterval(checkInterval);
                    window.location.href = '/game/play/{{ $session->code }}';
                    return;
                }

                // Check if player 2 left (only matters for host)
                @if($isHost)
                    if (!data.player2_id && {{ $session->player2_id ? 'true' : 'false' }}) {
                        // Player 2 was here but left
                        location.reload();
                    }
                @endif

                // Check if player 2 joined (for everyone)
                if (data.player2_id && !{{ $session->player2_id ? 'true' : 'false' }}) {
                    // Player 2 just joined
                    location.reload();
                }

            } catch (error) {
                console.error('Error checking lobby status:', error);
            }
        }

        // Start polling every second
        checkInterval = setInterval(checkLobbyStatus, 1000);

        // Start game button (host only)
        @if($isHost && $session->player2_id)
        document.getElementById('start-btn')?.addEventListener('click', async function() {
            this.disabled = true;
            this.textContent = 'Starting...';

            try {
                const response = await fetch('/session/start', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        code: '{{ $session->code }}'
                    })
                });

                const data = await response.json();

                if (data.success) {
                    clearInterval(checkInterval);
                    window.location.href = '/game/play/{{ $session->code }}';
                } else {
                    alert(data.message || 'Could not start game');
                    this.disabled = false;
                    this.textContent = '🚀 Start Game';
                }
            } catch (error) {
                alert('Error starting game!');
                this.disabled = false;
                this.textContent = '🚀 Start Game';
            }
        });
        @endif

        // Leave lobby button
        document.getElementById('leave-lobby-btn').addEventListener('click', async function() {
            if (!confirm('Are you sure you want to leave?')) {
                return;
            }

            try {
                const response = await fetch('/session/leave', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({
                        code: '{{ $session->code }}'
                    })
                });

                const data = await response.json();

                if (data.success) {
                    clearInterval(checkInterval);
                    window.location.href = '/menu';
                }
            } catch (error) {
                console.error('Error leaving:', error);
                clearInterval(checkInterval);
                window.location.href = '/menu';
            }
        });
    </script>
</body>
</html>