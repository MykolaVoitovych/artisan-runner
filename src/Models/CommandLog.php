<?php

namespace Mykolavoitovych\ArtisanRunner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Mykolavoitovych\ArtisanRunner\Enums\CommandStatus;

/**
 * @property int $id
 * @property string $command
 * @property string|null $output
 * @property CommandStatus $status
 * @property int|null $exit_code
 * @property int|null $admin_id
 * @property string|null $admin_name
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CommandLog extends Model
{
    protected $table = 'artisan_runner_command_logs';

    protected $fillable = [
        'command',
        'output',
        'status',
        'exit_code',
        'admin_id',
        'admin_name',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => CommandStatus::class,
            'exit_code' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function appendOutput(string $output): void
    {
        DB::statement(
            "UPDATE {$this->getTable()} SET output = CONCAT(COALESCE(output, ''), ?) WHERE id = ?",
            [$output, $this->id]
        );

        $this->output = ($this->output ?? '') . $output;
    }

    public function markAsRunning(): void
    {
        $this->update([
            'status' => CommandStatus::Running,
            'started_at' => now(),
        ]);
    }

    public function markAsFinished(int $exitCode): void
    {
        $this->update([
            'status' => $exitCode === 0 ? CommandStatus::Completed : CommandStatus::Failed,
            'exit_code' => $exitCode,
            'completed_at' => now(),
        ]);
    }

    public function getDurationAttribute(): ?string
    {
        if (! $this->started_at) {
            return null;
        }

        $end = $this->completed_at ?? now();

        return $this->started_at->diffForHumans($end, true);
    }
}
