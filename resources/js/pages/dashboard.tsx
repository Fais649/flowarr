import { Head, Link, router, usePoll } from '@inertiajs/react';
import { DateText } from '@/components/date-text';
import { EmptyState } from '@/components/empty-state';
import { MetricCard } from '@/components/metric-card';
import { ProcessingBanner } from '@/components/processing-banner';
import { ProgressBar } from '@/components/progress-bar';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { formatDuration } from '@/lib/utils';
import { dashboard } from '@/routes';
import { show as showExecution } from '@/routes/executions';
import { create, show } from '@/routes/libraries';
import type { ExecutionStatus, ProcessingState } from '@/types/models';
import { JobTypeLabels } from '@/types/models';

type ProcessingExecution = {
    id: number;
    file_path: string;
    status: ExecutionStatus;
    job_type: string;
    library: string;
    progress: number | null;
    message: string | null;
    started_at: string | null;
    duration: number | null;
};

type QueuedByType = {
    job_type: string;
    count: number;
};

export default function Dashboard({
    metrics,
    processing,
    processingExecutions,
    queuedByType,
    recentExecutions,
    libraries,
}: {
    metrics: {
        libraryCount: number;
        pendingExecutions: number;
        failedToday: number;
        completedToday?: number;
        processingCount: number;
    };
    processing?: ProcessingState;
    processingExecutions: ProcessingExecution[];
    queuedByType: QueuedByType[];
    recentExecutions: {
        id: number;
        file_path: string;
        status: ExecutionStatus;
        library: string;
        job_type: string;
        created_at: string;
    }[];
    libraries: {
        id: number;
        base_path: string;
        status: string;
        enabled_jobs: number;
        last_scan: string | null;
    }[];
}) {
    usePoll(5000, {
        only: [
            'metrics',
            'processing',
            'processingExecutions',
            'queuedByType',
            'recentExecutions',
            'libraries',
        ],
    });

    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <ProcessingBanner processing={processing} />

                <div className="grid auto-rows-min gap-4 md:grid-cols-4">
                    <MetricCard
                        label="Libraries"
                        value={metrics.libraryCount}
                    />
                    <MetricCard
                        label="Pending"
                        value={metrics.pendingExecutions}
                    />
                    <MetricCard
                        label="Processing"
                        value={metrics.processingCount}
                    />
                    <MetricCard
                        label="Failed Today"
                        value={metrics.failedToday}
                        trend={
                            metrics.completedToday
                                ? {
                                      value: `${metrics.completedToday} completed`,
                                      positive: true,
                                  }
                                : undefined
                        }
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Worker Activity</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {processingExecutions.length === 0 ? (
                            <div className="space-y-2">
                                <p className="text-sm text-muted-foreground">
                                    Nothing is being processed right now.
                                </p>
                                {queuedByType.length > 0 && (
                                    <div className="flex flex-wrap gap-2">
                                        {queuedByType.map((qt) => (
                                            <Badge
                                                key={qt.job_type}
                                                variant="secondary"
                                            >
                                                {JobTypeLabels[qt.job_type] ??
                                                    qt.job_type}
                                                : {qt.count} queued
                                            </Badge>
                                        ))}
                                    </div>
                                )}
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {processingExecutions.map((exec) => (
                                    <div
                                        key={exec.id}
                                        className="space-y-2 border-b pb-3 last:border-0"
                                    >
                                        <div className="flex items-center justify-between gap-4">
                                            <div className="min-w-0 flex-1">
                                                <Link
                                                    href={showExecution(
                                                        exec.id,
                                                    )}
                                                    className="block truncate text-sm font-medium hover:underline"
                                                >
                                                    {exec.file_path}
                                                </Link>
                                                <p className="text-xs text-muted-foreground">
                                                    {exec.library} /{' '}
                                                    {JobTypeLabels[
                                                        exec.job_type
                                                    ] ?? exec.job_type}
                                                    {exec.duration !== null &&
                                                        ` · ${formatDuration(exec.duration)}`}
                                                    {exec.message &&
                                                        ` · ${exec.message}`}
                                                </p>
                                            </div>
                                            <div className="flex items-center gap-2">
                                                {exec.progress !== null && (
                                                    <span className="text-xs text-muted-foreground tabular-nums">
                                                        {exec.progress.toFixed(
                                                            0,
                                                        )}
                                                        %
                                                    </span>
                                                )}
                                                <StatusBadge
                                                    status={exec.status}
                                                />
                                            </div>
                                        </div>
                                        <ProgressBar
                                            value={exec.progress}
                                            paused={exec.status === 'paused'}
                                        />
                                    </div>
                                ))}
                                {queuedByType.length > 0 && (
                                    <div className="flex flex-wrap gap-2">
                                        {queuedByType.map((qt) => (
                                            <Badge
                                                key={qt.job_type}
                                                variant="secondary"
                                            >
                                                {JobTypeLabels[qt.job_type] ??
                                                    qt.job_type}
                                                : {qt.count} queued
                                            </Badge>
                                        ))}
                                    </div>
                                )}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {libraries.length === 0 ? (
                    <EmptyState
                        title="No libraries configured"
                        description="Add your first media library to get started with automated transcoding."
                        action={{
                            label: 'Add Library',
                            onClick: () => router.visit(create()),
                        }}
                    />
                ) : (
                    <>
                        <Card>
                            <CardHeader>
                                <CardTitle>Recent Executions</CardTitle>
                            </CardHeader>
                            <CardContent>
                                {recentExecutions.length === 0 ? (
                                    <p className="py-4 text-center text-sm text-muted-foreground">
                                        No executions yet. Configure a library
                                        and trigger a scan.
                                    </p>
                                ) : (
                                    <div className="space-y-3">
                                        {recentExecutions.map((exec) => (
                                            <div
                                                key={exec.id}
                                                className="flex items-center justify-between border-b pb-2 last:border-0"
                                            >
                                                <div className="min-w-0 flex-1">
                                                    <Link
                                                        href={showExecution(
                                                            exec.id,
                                                        )}
                                                        className="block truncate text-sm font-medium hover:underline"
                                                    >
                                                        {exec.file_path}
                                                    </Link>
                                                    <p className="text-xs text-muted-foreground">
                                                        {exec.library} /{' '}
                                                        {JobTypeLabels[
                                                            exec.job_type
                                                        ] ?? exec.job_type}
                                                    </p>
                                                </div>
                                                <div className="ml-4 flex items-center gap-2">
                                                    <StatusBadge
                                                        status={exec.status}
                                                    />
                                                    <DateText
                                                        value={exec.created_at}
                                                        format="date"
                                                        className="text-xs text-muted-foreground"
                                                    />
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Library Health</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3">
                                    {libraries.map((lib) => (
                                        <div
                                            key={lib.id}
                                            className="flex items-center justify-between border-b pb-2 last:border-0"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <Link
                                                    href={show(lib.id)}
                                                    className="text-sm font-medium hover:underline"
                                                >
                                                    {lib.base_path}
                                                </Link>
                                                <p className="text-xs text-muted-foreground">
                                                    {lib.enabled_jobs} workers
                                                    enabled
                                                </p>
                                            </div>
                                            <div className="ml-4 flex items-center gap-2">
                                                <StatusBadge
                                                    status={lib.status}
                                                />
                                                {lib.last_scan ? (
                                                    <DateText
                                                        value={lib.last_scan}
                                                        format="date"
                                                        className="text-xs text-muted-foreground"
                                                    />
                                                ) : (
                                                    <span className="text-xs text-muted-foreground">
                                                        Never scanned
                                                    </span>
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </>
                )}
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
