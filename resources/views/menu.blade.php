<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mini Games</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen flex items-center justify-center">
    <div class="max-w-2xl mx-auto p-8">
        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-6xl font-bold mb-4">🎮 Mini Games</h1>
            <p class="text-gray-400 text-lg">Choose a game to play with friends</p>
        </div>

        <!-- Games List -->
        <div class="space-y-4">

            <!-- Word Sync Game -->
            <a href="/game/word-sync" class="block group">
                <div class="bg-gradient-to-r from-purple-600 to-blue-600 p-6 rounded-lg hover:scale-105 transition transform duration-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold mb-2">🔤 Word Sync</h2>
                            <p class="text-gray-100">Converge on the same word with your partner</p>
                            <span class="inline-block mt-3 text-sm text-gray-200">2 Players • Real-time</span>
                        </div>
                        <div class="text-4xl group-hover:translate-x-2 transition-transform">→</div>
                    </div>
                </div>
            </a>

            <!-- Placeholder for Future Game 1 -->
            <div class="block opacity-50 cursor-not-allowed">
                <div class="bg-gray-800 p-6 rounded-lg border-2 border-gray-700 border-dashed">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold mb-2 text-gray-500">🎲 Coming Soon</h2>
                            <p class="text-gray-500">More multiplayer games in development...</p>
                        </div>
                        <div class="text-4xl text-gray-700">🔒</div>
                    </div>
                </div>
            </div>

            <!-- Placeholder for Future Game 2 -->
            <div class="block opacity-50 cursor-not-allowed">
                <div class="bg-gray-800 p-6 rounded-lg border-2 border-gray-700 border-dashed">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold mb-2 text-gray-500">🎯 Coming Soon</h2>
                            <p class="text-gray-500">More multiplayer games in development...</p>
                        </div>
                        <div class="text-4xl text-gray-700">🔒</div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Footer -->
        <div class="text-center mt-12 text-gray-500 text-sm">
            <p>Built with Laravel • Multiplayer Games Platform</p>
        </div>
    </div>
</body>
</html>