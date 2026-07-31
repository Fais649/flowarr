import { Head, Link, router } from '@inertiajs/react';
import { DateText } from '@/components/date-text';
import { EmptyState } from '@/components/empty-state';
import { MetricCard } from '@/components/metric-card';
import { StatusBadge } from '@/components/status-badge';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';
import { create, show } from '@/routes/libraries';

type ProcessingExecution = {
    id: number;
    file_path: string;
    job_type: string;
    library: string;
    started_at: string | null;
    duration: number | null;
};

type QueuedByType = {
    job_type: string;
    count: number;
};

export default function Dashboard({
    metrics,
    processingExecutions,
    queuedByType,
    recentExecutions,
    libraries,
}: {
    metrics: {
        libraryCount: number;
        pendingExecutions: number;
        failedToday: number;
        processingCount: number;
    };
    processingExecutions: ProcessingExecution[];
    queuedByType: QueuedByType[];
    recentExecutions: {
        id: number;
        file_path: string;
        status: string;
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
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
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
                            metrics.failedToday > 0
                                ? {
                                      value: `${metrics.failedToday} failures`,
                                      positive: false,
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
                                    No active workers.
                                </p>
                                {queuedByType.length > 0 && (
                                    <div className="flex flex-wrap gap-2">
                                        {queuedByType.map((qt) => (
                                            <Badge
                                                key={qt.job_type}
                                                variant="secondary"
                                            >
                                                {qt.job_type}: {qt.count} queued
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
                                        className="flex items-center justify-between border-b pb-2 last:border-0"
                                    >
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium">
                                                {exec.file_path}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {exec.library} / {exec.job_type}
                                                {exec.duration !== null &&
                                                    ` · ${exec.duration}m`}
                                            </p>
                                        </div>
                                        <StatusBadge status="processing" />
                                    </div>
                                ))}
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
                                                    <p className="truncate text-sm font-medium">
                                                        {exec.file_path}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {exec.library} /{' '}
                                                        {exec.job_type}
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
                                                    {lib.enabled_jobs} jobs
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
