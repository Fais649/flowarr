import { ProgressBar } from '@/components/progress-bar';
import { StatusBadge } from '@/components/status-badge';
import type { ExecutionStatus } from '@/types/models';

/**
 * Status badge plus a progress bar while the execution is running.
 */
export function ExecutionStatusCell({
    status,
    progress,
    message,
}: {
    status: ExecutionStatus;
    progress: number | null;
    message?: string | null;
}) {
    const running = status === 'processing' || status === 'paused';

    return (
        <div className="flex min-w-32 flex-col gap-1">
            <div className="flex items-center gap-2">
                <StatusBadge status={status} />
                {running && progress !== null && (
                    <span className="text-xs text-muted-foreground tabular-nums">
                        {progress.toFixed(0)}%
                    </span>
                )}
            </div>
            {running && (
                <ProgressBar value={progress} paused={status === 'paused'} />
            )}
            {message && status !== 'completed' && (
                <span
                    className="max-w-56 truncate text-xs text-muted-foreground"
                    title={message}
                >
                    {message}
                </span>
            )}
        </div>
    );
}
