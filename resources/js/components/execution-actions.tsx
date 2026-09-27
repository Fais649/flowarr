import { router } from '@inertiajs/react';
import { Pause, Play, RotateCcw, Square, Trash2 } from 'lucide-react';
import {
    destroy,
    pause,
    resume,
    retry,
    stop,
} from '@/actions/App/Http/Controllers/ExecutionsController';
import { Button } from '@/components/ui/button';
import type { ExecutionStatus } from '@/types/models';
import { ACTIVE_STATUSES, RETRYABLE_STATUSES } from '@/types/models';

type Props = {
    execution: { id: number; status: ExecutionStatus };
    showLabels?: boolean;
    showDelete?: boolean;
};

export function ExecutionActions({
    execution,
    showLabels = false,
    showDelete = true,
}: Props) {
    const post = (url: string, confirmMessage?: string) => {
        if (confirmMessage && !confirm(confirmMessage)) {
            return;
        }

        router.post(url, {}, { preserveScroll: true });
    };

    const size = showLabels ? 'default' : 'sm';
    const iconClass = showLabels ? 'mr-1 size-4' : 'size-3';

    return (
        <div className="flex justify-end gap-1">
            {execution.status === 'processing' && (
                <Button
                    variant="outline"
                    size={size}
                    title="Pause"
                    onClick={() => post(pause.url({ execution: execution.id }))}
                >
                    <Pause className={iconClass} />
                    {showLabels && 'Pause'}
                </Button>
            )}
            {execution.status === 'paused' && (
                <Button
                    variant="outline"
                    size={size}
                    title="Resume"
                    onClick={() =>
                        post(resume.url({ execution: execution.id }))
                    }
                >
                    <Play className={iconClass} />
                    {showLabels && 'Resume'}
                </Button>
            )}
            {RETRYABLE_STATUSES.includes(execution.status) && (
                <Button
                    variant="outline"
                    size={size}
                    title="Retry"
                    onClick={() =>
                        post(
                            retry.url({ execution: execution.id }),
                            'Queue this execution again?',
                        )
                    }
                >
                    <RotateCcw className={iconClass} />
                    {showLabels && 'Retry'}
                </Button>
            )}
            {ACTIVE_STATUSES.includes(execution.status) && (
                <Button
                    variant="outline"
                    size={size}
                    title="Stop"
                    onClick={() =>
                        post(
                            stop.url({ execution: execution.id }),
                            'Stop this execution? A running process is terminated and its partial output discarded.',
                        )
                    }
                >
                    <Square className={iconClass} />
                    {showLabels && 'Stop'}
                </Button>
            )}
            {showDelete && (
                <Button
                    variant="outline"
                    size={size}
                    title="Delete"
                    onClick={() => {
                        if (!confirm('Delete this execution record?')) {
                            return;
                        }

                        router.delete(
                            destroy.url({ execution: execution.id }),
                            { preserveScroll: true },
                        );
                    }}
                >
                    <Trash2 className={iconClass} />
                    {showLabels && 'Delete'}
                </Button>
            )}
        </div>
    );
}
