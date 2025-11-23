<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Word Sync - Lobby</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <div class="container mx-auto px-4 py-8 max-w-3xl">

        <!-- Back Button -->
        <div class="mb-8">
            <a href="/menu" class="text-blue-400 hover:text-blue-300 flex items-center gap-2">
                ← Back to Menu
            </a>
        </div>

        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-5xl font-bold mb-4">🔤 Word Sync</h1>
            <p class="text-gray-400 text-lg">Think alike to win!</p>
        </div>

        <!-- Game Setup Card -->
        <div class="bg-gray-800 rounded-xl p-8 mb-8">

            <!-- Username Input (shown first) -->
            <div class="text-center mb-8">
                <h2 class="text-2xl font-bold mb-4">Enter Your Name</h2>
                <input
                    type="text"
                    id="username-input"
                    placeholder="Your username"
                    class="bg-gray-700 px-6 py-3 rounded-lg w-64 text-center text-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    maxlength="20"
                >
                <p class="text-gray-400 text-sm mt-2">This is how other players will see you</p>
            </div>

            <div class="flex items-center gap-4 my-8">
                <div class="flex-1 h-px bg-gray-700"></div>
                <div class="flex-1 h-px bg-gray-700"></div>
            </div>

            <!-- Create New Game -->
            <div class="text-center mb-8">
                <h2 class="text-2xl font-bold mb-4">Start a New Game</h2>
                <button id="create-btn" class="bg-green-600 hover:bg-green-700 px-12 py-4 rounded-lg font-bold text-lg transition duration-200 transform hover:scale-105">
                    🎮 Create Game
                </button>
                <p class="text-gray-400 text-sm mt-3">You'll get a code to share with your friend</p>
            </div>

            <!-- Divider -->
            <div class="flex items-center gap-4 my-8">
                <div class="flex-1 h-px bg-gray-700"></div>
                <span class="text-gray-500">OR</span>
                <div class="flex-1 h-px bg-gray-700"></div>
            </div>

            <!-- Join Existing Game -->
            <div class="text-center">
                <h2 class="text-2xl font-bold mb-4">Join a Game</h2>
                <div class="flex gap-3 justify-center">
                    <input
                        type="text"
                        id="code-input"
                        placeholder="ENTER CODE"
                        class="bg-gray-700 px-6 py-4 rounded-lg w-64 text-center text-2xl font-bold tracking-widest uppercase focus:outline-none focus:ring-2 focus:ring-blue-500"
                        maxlength="6"
                    >
                    <button id="join-btn" class="bg-blue-600 hover:bg-blue-700 px-8 py-4 rounded-lg font-bold transition duration-200">
                        Join →
                    </button>
                </div>
                <p class="text-gray-400 text-sm mt-3">Enter the 6-character code from your friend</p>
            </div>
        </div>

        <!-- How to Play -->
        <div class="bg-gray-800 rounded-xl p-8">
            <h3 class="text-xl font-bold mb-4">📖 How to Play</h3>
            <ol class="space-y-3 text-gray-300">
                <li class="flex gap-3">
                    <span class="font-bold text-blue-400">1.</span>
                    <span>Both players secretly enter a word (20 seconds)</span>
                </li>
                <li class="flex gap-3">
                    <span class="font-bold text-blue-400">2.</span>
                    <span>Words are revealed to both players</span>
                </li>
                <li class="flex gap-3">
                    <span class="font-bold text-blue-400">3.</span>
                    <span>Try to think of a word that connects both words</span>
                </li>
                <li class="flex gap-3">
                    <span class="font-bold text-blue-400">4.</span>
                    <span>Keep playing rounds until you both enter the same word!</span>
                </li>
            </ol>

            <div class="mt-6 bg-gray-900 p-4 rounded-lg">
                <p class="text-sm text-gray-400 mb-2"><strong>Example:</strong></p>
                <p class="text-sm">
                    <span class="text-green-400">Player 1:</span> "tree"
                    <span class="text-purple-400 ml-4">Player 2:</span> "chair"<br>
                    <span class="text-gray-500 text-xs">→ Next round both might think: "furniture" or "wood" or "garden"</span>
                </p>
            </div>
        </div>
    </div>

    <script>
        const usernameInput = document.getElementById('username-input');
        const createButton = document.getElementById('create-btn');
        const joinButton = document.getElementById('join-btn');
        const codeInput = document.getElementById('code-input');

        // Create Game
        createButton.addEventListener('click', async function() {
            const username = usernameInput.value.trim();

            if (!username || username.length < 2) {
                alert('Please enter a username (at least 2 characters)!');
                usernameInput.focus();
                return;
            }

            this.disabled = true;
            this.textContent = 'Creating...';

            try {
                const response = await fetch('/session/create', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ username })
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = '/game/lobby/' + data.code;
                } else {
                    alert('Error: ' + (data.message || 'Could not create game'));
                    this.disabled = false;
                    this.textContent = '🎮 Create Game';
                }
            } catch (error) {
                alert('Error creating game!');
                this.disabled = false;
                this.textContent = '🎮 Create Game';
            }
        });

        // Join Game
        joinButton.addEventListener('click', async function() {
            const username = usernameInput.value.trim();
            const code = codeInput.value.trim().toUpperCase();

            if (!username || username.length < 2) {
                alert('Please enter a username (at least 2 characters)!');
                usernameInput.focus();
                return;
            }

            if (code.length !== 6) {
                alert('Please enter a 6-character game code!');
                codeInput.focus();
                return;
            }

            this.disabled = true;
            this.textContent = 'Joining...';

            try {
                const response = await fetch('/session/join', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ code, username })
                });

                const data = await response.json();

                if (data.success) {
                    window.location.href = '/game/lobby/' + data.code;
                } else {
                    alert(data.message);
                    this.disabled = false;
                    this.textContent = 'Join →';
                }
            } catch (error) {
                alert('Error joining game!');
                this.disabled = false;
                this.textContent = 'Join →';
            }
        });

        // Allow Enter key
        codeInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                joinButton.click();
            }
        });

        usernameInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && codeInput.value.trim().length === 6) {
                joinButton.click();
            }
        });
    </script>
</body>
</html>