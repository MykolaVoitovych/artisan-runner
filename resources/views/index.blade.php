<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artisan Runner</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen font-sans antialiased">

{{-- Header --}}
<header class="bg-gray-900 border-b border-gray-800 px-6 py-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <h1 class="text-lg font-semibold text-white">Artisan Runner</h1>
    </div>
    <a href="{{ url(config('nova.path', 'nova')) }}" class="text-sm text-gray-400 hover:text-gray-200 transition-colors">
        ← Back to Nova
    </a>
</header>

<div class="max-w-6xl mx-auto px-6 py-8 space-y-8">

    {{-- Run Command Form --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl p-6">
        <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-4">Run Command</h2>

        @if ($errors->any())
            <div class="mb-4 bg-red-950 border border-red-800 text-red-300 rounded-lg px-4 py-3 text-sm">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('artisan-runner.store') }}" method="POST" class="flex gap-3">
            @csrf
            <div class="flex-1 relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 font-mono text-sm select-none">php artisan</span>
                <input
                    type="text"
                    name="command"
                    value="{{ old('command') }}"
                    placeholder="cache:clear"
                    list="available-commands"
                    autocomplete="off"
                    required
                    class="w-full bg-gray-800 border border-gray-700 rounded-lg pl-[7.5rem] pr-4 py-2.5 text-sm font-mono text-gray-100 placeholder-gray-600 focus:outline-none focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"
                >
                <datalist id="available-commands">
                    @foreach ($availableCommands as $cmd)
                        <option value="{{ $cmd }}">
                    @endforeach
                </datalist>
            </div>
            <button
                type="submit"
                class="bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition-colors whitespace-nowrap"
            >
                Run Command
            </button>
        </form>

        @if (!empty($forbiddenCommands))
            <div class="mt-3 flex items-start gap-2">
                <svg class="w-4 h-4 text-amber-500 mt-0.5 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/>
                </svg>
                <p class="text-xs text-amber-500/80">
                    Forbidden: <span class="font-mono">{{ implode(', ', $forbiddenCommands) }}</span>
                </p>
            </div>
        @endif
    </div>

    {{-- History --}}
    <div class="bg-gray-900 border border-gray-800 rounded-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-800 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider">Command History</h2>
            <span class="text-xs text-gray-600">{{ $commandLogs->total() }} total</span>
        </div>

        @if ($commandLogs->isEmpty())
            <div class="px-6 py-12 text-center text-gray-600 text-sm">
                No commands have been run yet.
            </div>
        @else
            <div class="divide-y divide-gray-800">
                @foreach ($commandLogs as $log)
                    <div class="px-6 py-4 flex items-center gap-4 hover:bg-gray-800/50 transition-colors">
                        {{-- Status Dot --}}
                        <span class="w-2 h-2 rounded-full shrink-0" style="background-color: {{ $log->status->color() }}"></span>

                        {{-- Command --}}
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('artisan-runner.show', $log) }}" class="font-mono text-sm text-indigo-400 hover:text-indigo-300 transition-colors truncate block">
                                php artisan {{ $log->command }}
                            </a>
                            <div class="flex items-center gap-3 mt-1 text-xs text-gray-600">
                                <span>{{ $log->created_at->diffForHumans() }}</span>
                                @if ($log->admin_name)
                                    <span>by {{ $log->admin_name }}</span>
                                @endif
                                @if ($log->started_at && $log->completed_at)
                                    <span>{{ $log->started_at->diffForHumans($log->completed_at, true) }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Status Badge --}}
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full border" style="color: {{ $log->status->color() }}; border-color: {{ $log->status->color() }}33; background-color: {{ $log->status->color() }}11">
                            {{ $log->status->label() }}
                        </span>

                        @if ($log->exit_code !== null)
                            <span class="text-xs font-mono text-gray-600">exit {{ $log->exit_code }}</span>
                        @endif

                        {{-- Actions --}}
                        <div class="flex items-center gap-2 shrink-0">
                            <a href="{{ route('artisan-runner.show', $log) }}" class="text-xs text-gray-500 hover:text-gray-300 transition-colors px-2 py-1 rounded hover:bg-gray-700">
                                View
                            </a>
                            <form action="{{ route('artisan-runner.destroy', $log) }}" method="POST" onsubmit="return confirm('Delete this log?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-gray-600 hover:text-red-400 transition-colors px-2 py-1 rounded hover:bg-gray-700">
                                    Delete
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>

            @if ($commandLogs->hasPages())
                <div class="px-6 py-4 border-t border-gray-800 flex items-center justify-between text-sm text-gray-500">
                    <span>Page {{ $commandLogs->currentPage() }} of {{ $commandLogs->lastPage() }}</span>
                    <div class="flex gap-2">
                        @if ($commandLogs->onFirstPage())
                            <span class="px-3 py-1 rounded border border-gray-800 text-gray-700 cursor-not-allowed">← Prev</span>
                        @else
                            <a href="{{ $commandLogs->previousPageUrl() }}" class="px-3 py-1 rounded border border-gray-700 hover:border-gray-500 hover:text-gray-300 transition-colors">← Prev</a>
                        @endif
                        @if ($commandLogs->hasMorePages())
                            <a href="{{ $commandLogs->nextPageUrl() }}" class="px-3 py-1 rounded border border-gray-700 hover:border-gray-500 hover:text-gray-300 transition-colors">Next →</a>
                        @else
                            <span class="px-3 py-1 rounded border border-gray-800 text-gray-700 cursor-not-allowed">Next →</span>
                        @endif
                    </div>
                </div>
            @endif
        @endif
    </div>

</div>
</body>
</html>
