<?php

namespace Vantage\ArtisanRunner\Enums;

enum CommandStatus: string
{
    case Pending = 'pending';
    case Running = 'running';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Running => 'Running',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => '#94a3b8',
            self::Running => '#f59e0b',
            self::Completed => '#22c55e',
            self::Failed => '#ef4444',
        };
    }

    public function isFinished(): bool
    {
        return in_array($this, [self::Completed, self::Failed]);
    }
}
