import { Head, Link, usePoll } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { useEffect, useRef } from 'react';
import { DateText } from '@/components/date-text';
import { ExecutionActions } from '@/components/execution-actions';
import { ProcessingBanner } from '@/components/processing-banner';
import { ProgressBar } from '@/components/progress-bar';
import { StatusBadge } from '@/components/status-badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatDuration } from '@/lib/utils';
import { dashboard } from '@/routes';
import { index } from '@/routes/executions';
import { show as showLibrary } from '@/routes/libraries';
import { show as showWorker } from '@/routes/workers';
import type { Execution } from '@/types/models';
import { ACTIVE_STATUSES, JobTypeLabels } from '@/types/models';

type ExecutionDetail = Execution & {
    output: string | null;
    duration_seconds: number | null;
    file_size: number | null;
    worker: { id: number; name: string; replace_original: boolean } | null;
};

function formatBytes(bytes: number | null): string {
    if (bytes === null) {
        return '-';
    }

    const units = ['B', 'KB', 'MB', 'GB', 'TB'];
    let value = bytes;
    let unit = 0;

    while (value >= 1024 && unit < units.length - 1) {
        value /= 1024;
        unit++;
    }

    return `${value.toFixed(unit === 0 ? 0 : 1)} ${units[unit]}`;
}

function Field({
    label,
    children,
}: {
    label: string;
    children: React.ReactNode;
}) {
    return (
        <div>
            <span className="text-sm text-muted-foreground">{label}</span>
            <div className="font-medium break-all">{children}</div>
        </div>
    );
}

export default function ExecutionDetailPage({
    execution,
}: {
    execution: ExecutionDetail;
}) {
    const isActive = ACTIVE_STATUSES.includes(execution.status);
    const isRunning =
        execution.status === 'processing' || execution.status === 'paused';
    const logRef = useRef<HTMLPreElement>(null);

    const { start, stop } = usePoll(
        2000,
        { only: ['execution'] },
        { autoStart: false },
    );

    useEffect(() => {
        if (isActive) {
            start();
        } else {
            stop();
        }
    }, [isActive, start, stop]);

    useEffect(() => {
        if (logRef.current) {
            logRef.current.scrollTop = logRef.current.scrollHeight;
        }
    }, [execution.output]);

    const library = execution.library_job?.library;

    return (
        <>
            <Head title={`Execution #${execution.id}`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex flex-wrap items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={index()}>
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <div className="min-w-0 flex-1">
                        <h1 className="text-2xl font-bold">
                            Execution #{execution.id}
                        </h1>
                        <div className="mt-1 flex items-center gap-2">
                            <StatusBadge status={execution.status} />
                            {isRunning && execution.progress !== null && (
                                <span className="text-sm text-muted-foreground tabular-nums">
                                    {execution.progress.toFixed(1)}%
                                </span>
                            )}
                        </div>
                    </div>
                    <ExecutionActions execution={execution} showLabels />
                </div>

                {isActive && <ProcessingBanner />}

                {isRunning && (
                    <ProgressBar
                        value={execution.progress}
                        paused={execution.status === 'paused'}
                        className="h-2"
                    />
                )}

                {execution.message && execution.status !== 'completed' && (
                    <Alert
                        variant={
                            execution.status === 'failed'
                                ? 'destructive'
                                : 'default'
                        }
                    >
                        <AlertTitle>
                            {execution.status === 'failed'
                                ? 'Failure reason'
                                : 'Status'}
                        </AlertTitle>
                        <AlertDescription className="font-mono text-xs whitespace-pre-wrap">
                            {execution.message}
                        </AlertDescription>
                    </Alert>
                )}

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <Field label="File">{execution.file_path}</Field>
                            <Field label="Size">
                                {formatBytes(execution.file_size)}
                            </Field>
                            <Field label="Library">
                                {library ? (
                                    <Link
                                        href={showLibrary(library.id)}
                                        className="hover:underline"
                                    >
                                        {library.base_path}
                                    </Link>
                                ) : (
                                    '-'
                                )}
                            </Field>
                            <Field label="Job Type">
                                {JobTypeLabels[execution.library_job?.job_id] ??
                                    execution.library_job?.job_id ??
                                    '-'}
                            </Field>
                            <Field label="Worker">
                                {execution.worker ? (
                                    <Link
                                        href={showWorker(execution.worker.id)}
                                        className="hover:underline"
                                    >
                                        {execution.worker.name}
                                        {execution.worker.replace_original &&
                                            ' (replaces original)'}
                                    </Link>
                                ) : (
                                    '-'
                                )}
                            </Field>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Timing</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <Field label="Queued">
                                <DateText value={execution.created_at} />
                            </Field>
                            <Field label="Started">
                                {execution.started_at ? (
                                    <DateText value={execution.started_at} />
                                ) : (
                                    '-'
                                )}
                            </Field>
                            <Field label="Finished">
                                {execution.finished_at ? (
                                    <DateText value={execution.finished_at} />
                                ) : (
                                    '-'
                                )}
                            </Field>
                            <Field label="Duration">
                                {formatDuration(execution.duration_seconds)}
                            </Field>
                            {isRunning && execution.heartbeat_at && (
                                <Field label="Last heartbeat">
                                    <DateText value={execution.heartbeat_at} />
                                </Field>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Output</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {execution.output ? (
                            <pre
                                ref={logRef}
                                className="max-h-96 overflow-auto rounded-md bg-muted p-3 font-mono text-xs whitespace-pre-wrap"
                            >
                                {execution.output}
                            </pre>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                {isActive
                                    ? 'No output yet.'
                                    : 'No output was recorded.'}
                            </p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

ExecutionDetailPage.layout = (page: React.ReactNode) => (
    <AppLayout
        breadcrumbs={[
            { title: 'Dashboard', href: dashboard() },
            { title: 'Executions', href: index() },
            { title: 'Detail', href: '#' },
        ]}
    >
        {page}
    </AppLayout>
);
