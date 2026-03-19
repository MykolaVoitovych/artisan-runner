<?php

namespace Mykolavoitovych\ArtisanRunner\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Mykolavoitovych\ArtisanRunner\Enums\CommandStatus;
use Mykolavoitovych\ArtisanRunner\Jobs\RunArtisanCommandJob;
use Mykolavoitovych\ArtisanRunner\Models\CommandLog;

class
ArtisanRunnerController extends Controller
{
    public function index(): View
    {
        $commandLogs = CommandLog::query()
            ->orderByDesc('created_at')
            ->paginate(20);

        $availableCommands = array_keys(Artisan::all());
        sort($availableCommands);

        $forbiddenCommands = config('artisan-runner.forbidden_commands', []);

        return view('artisan-runner::index', compact('commandLogs', 'availableCommands', 'forbiddenCommands'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'command' => ['required', 'string', 'max:500'],
        ]);

        $command = trim($request->input('command'));
        $commandName = explode(' ', $command)[0];

        $forbiddenCommands = config('artisan-runner.forbidden_commands', []);

        foreach ($forbiddenCommands as $forbidden) {
            if (str_starts_with($commandName, $forbidden) || fnmatch($forbidden, $commandName)) {
                return back()
                    ->withInput()
                    ->withErrors(['command' => "Command \"{$commandName}\" is forbidden."]);
            }
        }

        $availableCommands = array_keys(Artisan::all());

        if (! in_array($commandName, $availableCommands, true)) {
            return back()
                ->withInput()
                ->withErrors(['command' => "Command \"{$commandName}\" does not exist."]);
        }

        $admin = Auth::guard(config('artisan-runner.guard'))->user();

        $commandLog = CommandLog::create([
            'command' => $command,
            'status' => CommandStatus::Pending,
            'admin_id' => $admin?->id,
            'admin_name' => $admin?->name,
        ]);

        RunArtisanCommandJob::dispatch($commandLog->id);

        return redirect()->route('artisan-runner.show', $commandLog);
    }

    public function show(CommandLog $commandLog): View
    {
        return view('artisan-runner::show', compact('commandLog'));
    }

    public function output(CommandLog $commandLog, Request $request): JsonResponse
    {
        $offset = (int) $request->query('offset', 0);
        $fresh = $commandLog->fresh();
        $currentOutput = $fresh?->output ?? '';

        return response()->json([
            'output' => substr($currentOutput, $offset),
            'offset' => strlen($currentOutput),
            'status' => $fresh?->status->value,
            'exit_code' => $fresh?->exit_code,
        ]);
    }

    public function destroy(CommandLog $commandLog): RedirectResponse
    {
        $commandLog->delete();

        return redirect()->route('artisan-runner.index');
    }
}
