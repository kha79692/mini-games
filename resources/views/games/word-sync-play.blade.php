<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Word Sync - Playing</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <div class="max-w-4xl mx-auto p-8">

        <!-- Header with Leave Button -->
        <div class="flex justify-between items-center mb-8">
            <div></div>
            <div class="text-center">
                <h1 class="text-4xl font-bold mb-2">🔤 Word Sync</h1>
                <div class="flex items-center justify-center gap-8 text-lg">
                    <span class="text-green-400" id="player1-name">{{ $session->player1_name }}</span>
                    <span class="text-gray-500">vs</span>
                    <span class="text-purple-400" id="player2-name">{{ $session->player2_name }}</span>
                </div>
            </div>
            <button id="leave-game-btn" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg text-sm">
                🚪 Leave
            </button>
        </div>

        <!-- Main Game Container -->
        <div id="game-container">
            <div class="text-center py-12">
                <div class="text-4xl mb-4">⏳</div>
                <p class="text-gray-400">Loading game...</p>
            </div>
        </div>

    </div>

    <script>
        const sessionCode = '{{ $session->code }}';
        const csrfToken = '{{ csrf_token() }}';
        const playerId = '{{ session()->getId() }}';
        const isHost = '{{ $session->player1_id }}' === playerId;

        let currentRound = {{ $session->current_round }};
        let lastKnownRound = {{ $session->current_round }};
        let player1Name = '{{ $session->player1_name }}';
        let player2Name = '{{ $session->player2_name }}';

        let wordSubmitted = false;
        let gameStateInterval;
        let previousWords = null;
        let isStartingNextRound = false;
        let isCheckingCompletion = false;
        let hasShownResults = false;

        // Leave game button
        document.getElementById('leave-game-btn').addEventListener('click', async function() {
            if (!confirm('Are you sure you want to leave? This will end the game for both players.')) {
                return;
            }

            try {
                await fetch('/session/leave', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ code: sessionCode })
                });
                window.location.href = '/menu';
            } catch (error) {
                window.location.href = '/menu';
            }
        });

        // Poll game state every second
        async function pollGameState() {
            try {
                const response = await fetch('/game/state', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ code: sessionCode })
                });

                const data = await response.json();

                if (!data.success) {
                    return;
                }

                // Detect round change (highest priority)
                if (data.current_round !== lastKnownRound) {
                    console.log('Round changed from', lastKnownRound, 'to', data.current_round);
                    lastKnownRound = data.current_round;
                    currentRound = data.current_round;
                    wordSubmitted = false;
                    isCheckingCompletion = false;
                    hasShownResults = false;
                    showInputScreen(data.time_remaining, data.previous_round);
                    return;
                }

                // Check if we're waiting for other player
                const isWaiting = document.querySelector('.animate-pulse') !== null;

                // If waiting and both submitted, show results
                if (isWaiting && wordSubmitted && !hasShownResults) {
                    checkRoundCompletion();
                }

                // Check if we need to show input screen (first load)
                const shouldShowInput = !document.getElementById('timer') && !isWaiting && !hasShownResults;

                if (shouldShowInput) {
                    showInputScreen(data.time_remaining, data.previous_round);
                    return;
                }

                // Sync timer with server (but don't override if close)


                if (data.previous_round) {
                    previousWords = data.previous_round;
                }

            } catch (error) {
                console.error('Error polling game state:', error);
            }
        }

        // Show input screen
        let clientTimerInterval = null;

        function showInputScreen(timeRemaining = 20, prevWords = null) {
            // Clear any existing client timer
            if (clientTimerInterval) {
                clearInterval(clientTimerInterval);
                clientTimerInterval = null;
            }

            let html = `
                <div class="text-center mb-8">
                    <div class="inline-block bg-gray-800 px-6 py-3 rounded-full">
                        <span class="text-gray-400">Round</span>
                        <span class="text-2xl font-bold ml-2">${currentRound}</span>
                    </div>
                </div>
            `;

            if (prevWords && prevWords.player1_word && prevWords.player2_word) {
                html += `
                    <div class="mb-8">
                        <h3 class="text-center text-xl font-bold mb-4 text-gray-400">Previous Round:</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-green-900 bg-opacity-20 p-4 rounded-lg border border-green-600">
                                <p class="text-xs text-gray-400 mb-1">${player1Name}</p>
                                <p class="text-xl font-bold text-green-400">${prevWords.player1_word}</p>
                            </div>
                            <div class="bg-purple-900 bg-opacity-20 p-4 rounded-lg border border-purple-600">
                                <p class="text-xs text-gray-400 mb-1">${player2Name}</p>
                                <p class="text-xl font-bold text-purple-400">${prevWords.player2_word}</p>
                            </div>
                        </div>
                    </div>
                `;
            }

            html += `
                <div class="text-center mb-8">
                    <div id="timer" class="text-6xl font-bold text-yellow-400">${timeRemaining}</div>
                    <p class="text-gray-400 mt-2">seconds remaining</p>
                </div>

                <div class="bg-gray-800 rounded-xl p-8">
    <h2 class="text-2xl font-bold text-center mb-6">Enter Your Word</h2>
    <div class="flex flex-col gap-4 items-center"> <!-- change here -->
        <input
            type="text"
            id="word-input"
            placeholder="Type your word..."
            class="bg-gray-700 px-6 py-4 rounded-lg w-96 text-center text-xl focus:outline-none focus:ring-2 focus:ring-blue-500"
            maxlength="30"
            autofocus
        >
        <button id="submit-btn" class="bg-blue-600 hover:bg-blue-700 px-8 py-4 rounded-lg font-bold transition">
            Submit →
        </button>
    </div>
    <p class="text-gray-400 text-sm text-center mt-4">
        ${currentRound === 1 ? 'Enter any word you like!' : 'Think of a word that connects both previous words!'}
    </p>
</div>

            `;

            document.getElementById('game-container').innerHTML = html;

            const wordInput = document.getElementById('word-input');
            const submitBtn = document.getElementById('submit-btn');
            const timerEl = document.getElementById('timer');

            submitBtn.addEventListener('click', () => submitWord(wordInput.value));
            wordInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') submitWord(wordInput.value);
            });

            // Start client-side countdown
            let localTime = timeRemaining;
            clientTimerInterval = setInterval(() => {
                if (!wordSubmitted && timerEl) {
                    localTime--;

                    if (localTime < 0) localTime = 0;

                    timerEl.textContent = localTime;

                    if (localTime <= 5) {
                        timerEl.classList.add('text-red-500');
                        timerEl.classList.remove('text-yellow-400');
                    }

                    if (localTime <= 0 && !wordSubmitted) {
                        clearInterval(clientTimerInterval);
                        submitWord(wordInput ? wordInput.value : '');
                    }
                }
            }, 1000);
        }


        async function submitWord(word) {
            if (wordSubmitted) return;

            word = word.trim();
            if (!word) {
                word = '[no word]';
            }

            wordSubmitted = true;

            // Clear client timer
            if (clientTimerInterval) {
                clearInterval(clientTimerInterval);
                clientTimerInterval = null;
            }

            try {
                const response = await fetch('/game/submit-word', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        code: sessionCode,
                        word: word
                    })
                });

                const data = await response.json();

                if (data.both_submitted) {
                    if (data.matched) {
                        showWinScreen(data.words.player1);
                    } else {
                        hasShownResults = true;
                        showWordsReveal(data.words);
                    }
                } else {
                    showWaiting();
                }
            } catch (error) {
                console.error('Error submitting word:', error);
                alert('Error submitting word!');
                wordSubmitted = false;
            }
        }

        // Show waiting screen
        function showWaiting() {
            document.getElementById('game-container').innerHTML = `
                <div class="bg-gray-800 rounded-xl p-12 text-center">
                    <div class="text-6xl mb-6">⏳</div>
                    <h2 class="text-3xl font-bold mb-4">Word Submitted!</h2>
                    <p class="text-gray-400 text-lg">Waiting for other player...</p>
                    <div class="mt-8">
                        <div class="animate-pulse text-yellow-400 text-2xl">●</div>
                    </div>
                </div>
            `;
        }

        // Check if round completed
        async function checkRoundCompletion() {
            if (isCheckingCompletion || hasShownResults) return;

            isCheckingCompletion = true;

            try {
                const response = await fetch('/game/check-round', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ code: sessionCode })
                });

                const data = await response.json();

                if (data.both_submitted) {
                    hasShownResults = true;
                    if (data.matched) {
                        showWinScreen(data.words.player1);
                    } else {
                        showWordsReveal(data.words);
                    }
                }
            } catch (error) {
                console.error('Error checking round:', error);
            } finally {
                isCheckingCompletion = false;
            }
        }

        // Show revealed words
        function showWordsReveal(words) {
            document.getElementById('game-container').innerHTML = `
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold mb-2">Words Revealed!</h2>
                    <p class="text-gray-400">Here's what you both said...</p>
                </div>

                <div class="grid grid-cols-2 gap-6 mb-8">
                    <div class="bg-green-900 bg-opacity-30 p-8 rounded-xl border-2 border-green-600">
                        <p class="text-sm text-gray-400 mb-3">${player1Name}</p>
                        <p class="text-4xl font-bold">${words.player1}</p>
                    </div>
                    <div class="bg-purple-900 bg-opacity-30 p-8 rounded-xl border-2 border-purple-600">
                        <p class="text-sm text-gray-400 mb-3">${player2Name}</p>
                        <p class="text-4xl font-bold">${words.player2}</p>
                    </div>
                </div>

                <div class="bg-yellow-900 bg-opacity-20 border-2 border-yellow-600 rounded-xl p-6 mb-8">
                    <p class="text-center text-yellow-300">
                        ❌ Not a match! ${isHost ? 'Starting' : 'Host is starting'} next round in <span id="countdown">5</span> seconds...
                    </p>
                </div>
            `;

            let countdown = 5;
            let nextRoundCalled = false;
            const countdownEl = document.getElementById('countdown');

            const countdownInterval = setInterval(() => {
                countdown--;
                if (countdownEl) countdownEl.textContent = countdown;

                if (countdown <= 0) {
                    clearInterval(countdownInterval);

                    if (isHost && !nextRoundCalled) {
                        nextRoundCalled = true;
                        startNextRound();
                    }
                }
            }, 1000);
        }

        // Start next round (host only)
        async function startNextRound() {
            if (!isHost || isStartingNextRound) return;

            isStartingNextRound = true;

            try {
                const response = await fetch('/game/next-round', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({ code: sessionCode })
                });

                const data = await response.json();

                if (data.success) {
                    console.log('Next round started:', data.current_round);
                    currentRound = data.current_round;
                    lastKnownRound = data.current_round;
                    wordSubmitted = false;
                    isStartingNextRound = false;
                    isCheckingCompletion = false;
                    hasShownResults = false;
                }
            } catch (error) {
                console.error('Error starting next round:', error);
                isStartingNextRound = false;
            }
        }

        // Show win screen
        function showWinScreen(winningWord) {
            if (gameStateInterval) {
                clearInterval(gameStateInterval);
                gameStateInterval = null;
            }

            fetch('/game/complete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    code: sessionCode,
                    winning_word: winningWord
                })
            });

            document.getElementById('game-container').innerHTML = `
                <div class="text-center">
                    <div class="text-8xl mb-8 animate-bounce">🎉</div>
                    <h1 class="text-5xl font-bold mb-4 bg-gradient-to-r from-green-400 to-blue-500 text-transparent bg-clip-text">
                        YOU SYNCED!
                    </h1>
                    <p class="text-2xl text-gray-300 mb-8">
                        You both said: <span class="font-bold text-yellow-400">"${winningWord}"</span>
                    </p>

                    <div class="bg-gray-800 rounded-xl p-8 mb-8 inline-block">
                        <p class="text-gray-400 mb-2">Rounds taken:</p>
                        <p class="text-6xl font-bold text-green-400">${currentRound}</p>
                    </div>

                    <div class="flex gap-4 justify-center">
                        <button onclick="window.location.href='/menu'" class="bg-blue-600 hover:bg-blue-700 px-8 py-3 rounded-lg font-bold">
                            🏠 Back to Menu
                        </button>
                        <button onclick="window.location.href='/game/word-sync'" class="bg-green-600 hover:bg-green-700 px-8 py-3 rounded-lg font-bold">
                            🔄 Play Again
                        </button>
                    </div>
                </div>
            `;
        }

        // Check if game still exists
        async function checkGameExists() {
            try {
                const response = await fetch('/session/lobby-status/' + sessionCode);
                const data = await response.json();

                if (!data.exists) {
                    alert('The game has ended. Returning to menu...');
                    if (gameStateInterval) {
                        clearInterval(gameStateInterval);
                    }
                    window.location.href = '/menu';
                }
            } catch (error) {
                console.error('Error checking game:', error);
            }
        }

        // Start polling - NEVER STOP IT (except on win)
        gameStateInterval = setInterval(() => {
            pollGameState();
            checkGameExists();
        }, 1000);

        // Initial load
        pollGameState();
    </script>
</body>
</html>