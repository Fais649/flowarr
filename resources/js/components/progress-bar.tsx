import { cn } from '@/lib/utils';

export function ProgressBar({
    value,
    paused = false,
    className,
}: {
    value: number | null;
    paused?: boolean;
    className?: string;
}) {
    const indeterminate = value === null;
    const percent = Math.max(0, Math.min(100, value ?? 0));

    return (
        <div
            role="progressbar"
            aria-valuemin={0}
            aria-valuemax={100}
            aria-valuenow={indeterminate ? undefined : Math.round(percent)}
            className={cn(
                'relative h-1.5 w-full overflow-hidden rounded-full bg-muted',
                className,
            )}
        >
            <div
                className={cn(
                    'h-full rounded-full transition-[width] duration-500',
                    paused ? 'bg-orange-500' : 'bg-primary',
                    indeterminate && !paused && 'w-1/3 animate-pulse',
                )}
                style={indeterminate ? undefined : { width: `${percent}%` }}
            />
        </div>
    );
}
