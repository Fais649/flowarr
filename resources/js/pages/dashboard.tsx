import { Head, Link } from '@inertiajs/react';
import { EmptyState } from '@/components/empty-state';
import { MetricCard } from '@/components/metric-card';
import { StatusBadge } from '@/components/status-badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';

export default function Dashboard({
    metrics,
    recentExecutions,
    libraries,
}: {
    metrics: {
        libraryCount: number;
        pendingExecutions: number;
        failedToday: number;
        processingCount: number;
    };
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

                {libraries.length === 0 ? (
                    <EmptyState
                        title="No libraries configured"
                        description="Add your first media library to get started with automated transcoding."
                        action={{
                            label: 'Add Library',
                            onClick: () =>
                                (window.location.href = '/libraries/create'),
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
                                                    <span className="text-xs text-muted-foreground">
                                                        {new Date(
                                                            exec.created_at,
                                                        ).toLocaleDateString()}
                                                    </span>
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
                                                    href={`/libraries/${lib.id}`}
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
                                                <span className="text-xs text-muted-foreground">
                                                    {lib.last_scan
                                                        ? new Date(
                                                              lib.last_scan,
                                                          ).toLocaleDateString()
                                                        : 'Never scanned'}
                                                </span>
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
