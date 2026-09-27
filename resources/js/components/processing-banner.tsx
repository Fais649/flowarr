import { router, usePage } from '@inertiajs/react';
import { CalendarClock, PauseCircle, Play, Tv } from 'lucide-react';
import { startAll } from '@/actions/App/Http/Controllers/WorkersController';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import type { ProcessingState } from '@/types/models';

export function describePauseReasons(state: ProcessingState): string[] {
    return state.reasons.map((reason) => {
        switch (reason) {
            case 'manual':
                return 'Processing was paused manually.';
            case 'streams':
                return `${state.active_streams} active stream${state.active_streams === 1 ? '' : 's'} on your media server.`;
            case 'schedule':
                return state.window
                    ? `Outside the processing window (${state.window.start}–${state.window.end}).`
                    : 'Outside the processing window.';
        }
    });
}

export function ProcessingBanner({
    processing,
}: {
    processing?: ProcessingState | null;
}) {
    const shared = usePage().props.processing;
    const state = processing ?? shared;

    if (!state?.paused) {
        return null;
    }

    const Icon = state.reasons.includes('streams')
        ? Tv
        : state.reasons.includes('schedule')
          ? CalendarClock
          : PauseCircle;

    return (
        <Alert className="border-orange-300 bg-orange-50 text-orange-900 dark:border-orange-800 dark:bg-orange-950/40 dark:text-orange-200">
            <Icon className="size-4" />
            <AlertTitle>Processing is on hold</AlertTitle>
            <AlertDescription className="flex flex-wrap items-center justify-between gap-2 text-orange-900/80 dark:text-orange-200/80">
                <span>
                    {describePauseReasons(state).join(' ')} Running jobs are
                    suspended and queued jobs wait until processing resumes.
                </span>
                {state.manual && (
                    <Button
                        size="sm"
                        variant="outline"
                        onClick={() =>
                            router.post(
                                startAll.url(),
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        <Play className="mr-1 size-3" />
                        Resume processing
                    </Button>
                )}
            </AlertDescription>
        </Alert>
    );
}
