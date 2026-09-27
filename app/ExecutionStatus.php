<?php

namespace App;

enum ExecutionStatus: string
{
    case QUEUED = 'queued';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case STOPPED = 'stopped';
    case PAUSED = 'paused';
    case FAILED = 'failed';

    /**
     * Statuses of executions that still have work outstanding.
     *
     * @return list<self>
     */
    public static function active(): array
    {
        return [self::QUEUED, self::PROCESSING, self::PAUSED];
    }

    /**
     * Statuses of executions that can be queued again.
     *
     * @return list<self>
     */
    public static function retryable(): array
    {
        return [self::FAILED, self::STOPPED];
    }

    public function isActive(): bool
    {
        return in_array($this, self::active(), true);
    }

    public function isFinished(): bool
    {
        return ! $this->isActive();
    }

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }
}
