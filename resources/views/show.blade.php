<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Artisan Runner — {{ $commandLog->command }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@xterm/xterm@5.5.0/css/xterm.css" />
    <script src="https://cdn.jsdelivr.net/npm/@xterm/xterm@5.5.0/lib/xterm.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@xterm/addon-fit@0.10.0/lib/addon-fit.js"></script>
    <style>
        #terminal { height: calc(100vh - 200px); min-height: 400px; }
        .xterm { height: 100%; padding: 12px; }
        .xterm-viewport { border-radius: 0 0 0.75rem 0.75rem; }
    </style>
</head>
<body class="bg-gray-950 text-gray-100 min-h-screen font-sans antialiased">

{{-- Header --}}
<header class="bg-gray-900 border-b border-gray-800 px-6 py-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <a href="{{ route('artisan-runner.index') }}" class="text-gray-500 hover:text-gray-300 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
        </a>
        <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <code class="text-sm text-indigo-300 font-mono">php artisan {{ $commandLog->command }}</code>
    </div>

    <div class="flex items-center gap-4">
        {{-- Status --}}
        <div id="status-badge" class="flex items-center gap-2">
            <span
                id="status-dot"
                class="w-2 h-2 rounded-full"
                style="background-color: {{ $commandLog->status->color() }}"
            ></span>
            <span
                id="status-text"
                class="text-sm font-medium"
                style="color: {{ $commandLog->status->color() }}"
            >
                {{ $commandLog->status->label() }}
            </span>
        </div>

        {{-- Timing --}}
        <span id="timing" class="text-xs text-gray-600">
            @if ($commandLog->started_at && $commandLog->completed_at)
                {{ $commandLog->started_at->diffForHumans($commandLog->completed_at, true) }}
            @elseif ($commandLog->started_at)
                Running…
            @endif
        </span>

        {{-- Re-run --}}
        <form action="{{ route('artisan-runner.store') }}" method="POST">
            @csrf
            <input type="hidden" name="command" value="{{ $commandLog->command }}">
            <button type="submit" class="text-xs text-gray-400 hover:text-gray-200 border border-gray-700 hover:border-gray-500 px-3 py-1.5 rounded-lg transition-colors">
                Re-run
            </button>
        </form>
    </div>
</header>

{{-- Meta row --}}
<div class="flex items-center gap-6 px-6 py-3 bg-gray-900/50 border-b border-gray-800/50 text-xs text-gray-600">
    @if ($commandLog->admin_name)
        <span>by <span class="text-gray-400">{{ $commandLog->admin_name }}</span></span>
    @endif
    <span>started <span class="text-gray-400">{{ $commandLog->created_at->diffForHumans() }}</span></span>
    @if ($commandLog->exit_code !== null)
        <span>exit code <span class="font-mono {{ $commandLog->exit_code === 0 ? 'text-green-400' : 'text-red-400' }}">{{ $commandLog->exit_code }}</span></span>
    @endif
</div>

{{-- Terminal --}}
<div class="px-6 pt-4 pb-6">
    <div class="rounded-xl overflow-hidden border border-gray-800" style="background: #0d0d0d;">
        {{-- Terminal bar --}}
        <div class="flex items-center gap-1.5 px-4 py-2.5 bg-gray-800/60 border-b border-gray-700/50">
            <span class="w-3 h-3 rounded-full bg-red-500/70"></span>
            <span class="w-3 h-3 rounded-full bg-yellow-500/70"></span>
            <span class="w-3 h-3 rounded-full bg-green-500/70"></span>
            <span class="ml-2 text-xs text-gray-500 font-mono">php artisan {{ $commandLog->command }}</span>
        </div>
        <div id="terminal"></div>
    </div>
</div>

<script>
    const commandLogId = {{ $commandLog->id }};
    const initialStatus = '{{ $commandLog->status->value }}';
    const outputUrl = '{{ route('artisan-runner.output', $commandLog) }}';
    const statusColors = {
        pending: '#94a3b8',
        running: '#f59e0b',
        completed: '#22c55e',
        failed: '#ef4444',
    };
    const statusLabels = {
        pending: 'Pending',
        running: 'Running',
        completed: 'Completed',
        failed: 'Failed',
    };

    // Init xterm
    const term = new Terminal({
        theme: {
            background: '#0d0d0d',
            foreground: '#e2e8f0',
            cursor: '#6366f1',
            black: '#1a1a2e',
            brightBlack: '#4b5563',
            red: '#f87171',
            brightRed: '#ef4444',
            green: '#4ade80',
            brightGreen: '#22c55e',
            yellow: '#fbbf24',
            brightYellow: '#f59e0b',
            blue: '#60a5fa',
            brightBlue: '#3b82f6',
            magenta: '#c084fc',
            brightMagenta: '#a855f7',
            cyan: '#22d3ee',
            brightCyan: '#06b6d4',
            white: '#e2e8f0',
            brightWhite: '#f8fafc',
        },
        fontFamily: '"JetBrains Mono", "Fira Code", "Cascadia Code", Menlo, monospace',
        fontSize: 13,
        lineHeight: 1.5,
        cursorBlink: false,
        disableStdin: true,
        convertEol: true,
        scrollback: 5000,
    });

    const fitAddon = new FitAddon.FitAddon();
    term.loadAddon(fitAddon);
    term.open(document.getElementById('terminal'));
    fitAddon.fit();

    window.addEventListener('resize', () => fitAddon.fit());

    // Write existing output on load
    const existingOutput = @json($commandLog->output ?? '');
    if (existingOutput) {
        term.write(existingOutput);
    }

    // Poll for new output
    let offset = existingOutput.length;
    let pollTimer = null;
    let startTime = null;

    function updateStatus(status, exitCode) {
        const dot = document.getElementById('status-dot');
        const text = document.getElementById('status-text');
        const color = statusColors[status] ?? '#94a3b8';

        dot.style.backgroundColor = color;
        text.style.color = color;
        text.textContent = statusLabels[status] ?? status;

        if (status === 'completed' || status === 'failed') {
            if (exitCode !== null && exitCode !== undefined) {
                const meta = document.querySelector('#timing');
                if (meta) {
                    meta.textContent = `exit ${exitCode}`;
                }
            }
        }
    }

    function poll() {
        fetch(`${outputUrl}?offset=${offset}`)
            .then(r => r.json())
            .then(data => {
                if (data.output) {
                    term.write(data.output);
                    offset = data.offset;
                }

                updateStatus(data.status, data.exit_code);

                if (data.status === 'completed' || data.status === 'failed') {
                    clearInterval(pollTimer);
                    pollTimer = null;

                    // Show exit code in terminal
                    const color = data.status === 'completed' ? '\x1b[32m' : '\x1b[31m';
                    const reset = '\x1b[0m';
                    term.write(`\r\n${color}─── Process exited with code ${data.exit_code ?? '?'} ───${reset}\r\n`);
                }
            })
            .catch(() => {
                // Silently retry on network errors
            });
    }

    // Only poll if command is not already finished
    const finishedStatuses = ['completed', 'failed'];
    if (! finishedStatuses.includes(initialStatus)) {
        pollTimer = setInterval(poll, 300);
        startTime = Date.now();
    }
</script>
</body>
</html>
