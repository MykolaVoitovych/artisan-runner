<?php

namespace Mykolavoitovych\ArtisanRunner\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Mykolavoitovych\ArtisanRunner\Models\CommandLog;

class RunArtisanCommandJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 600;
    public int $tries = 1;

    public function __construct(
        public readonly int $commandLogId
    ) {
    }

    public function handle(): void
    {
        $commandLog = CommandLog::findOrFail($this->commandLogId);
        $commandLog->markAsRunning();

        $parts = $this->parseCommand($commandLog->command);

        $phpBinary = (new PhpExecutableFinder())->find() ?: 'php';

        $process = new Process(
            array_merge([$phpBinary, base_path('artisan')], $parts),
            base_path(),
            null,
            null,
            config('artisan-runner.process_timeout', 300)
        );

        $buffer = '';

        $process->run(function (string $type, string $chunk) use ($commandLog, &$buffer): void {
            $buffer .= $chunk;

            if (str_contains($buffer, "\n") || strlen($buffer) >= 256) {
                $commandLog->appendOutput($buffer);
                $buffer = '';
            }
        });

        if ($buffer !== '') {
            $commandLog->appendOutput($buffer);
        }

        $commandLog->markAsFinished($process->getExitCode() ?? 1);
    }

    public function failed(\Throwable $exception): void
    {
        $commandLog = CommandLog::find($this->commandLogId);

        if ($commandLog) {
            $commandLog->appendOutput("\n[Error] Job failed: " . $exception->getMessage() . "\n");
            $commandLog->markAsFinished(1);
        }
    }

    /**
     * @return array<int, string>
     */
    private function parseCommand(string $command): array
    {
        preg_match_all('/(?:[^\s"\']+|"[^"]*"|\'[^\']*\')+/', trim($command), $matches);

        return array_map(
            fn (string $arg) => trim($arg, '"\''),
            $matches[0]
        );
    }
}
