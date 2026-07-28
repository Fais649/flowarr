import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Play, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { toggleWorker } from '@/actions/App/Http/Controllers/LibrariesController';
import { DataTable } from '@/components/data-table';
import type { Column } from '@/components/data-table';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Switch } from '@/components/ui/switch';
import AppLayout from '@/layouts/app-layout';

type Worker = {
    id: number;
    name: string;
    job_type: string | null;
    pivot: { worker_id: number; library_id: number };
};

type Library = {
    id: number;
    base_path: string;
    status: string;
    scan_interval: number;
    last_scan: string | null;
    library_jobs: { id: number; job_id: string }[];
    workers: Worker[];
};

type Execution = {
    id: number;
    file_path: string;
    status: string;
    library_job: { id: number; job_id: string };
    created_at: string;
};


const JOB_TYPE_LABELS: Record<string, string> = {
    transcode_media: 'Transcode Media',
    extract_subs: 'Extract Subtitles',
    convert_sub: 'Convert Subtitles',
};

export default function LibraryDetail({
    library,
    allWorkers,
    recentExecutions,
}: {
    library: Library;
    allWorkers: Worker[];
    recentExecutions: Execution[];
}) {
    const [isDeleting, setIsDeleting] = useState(false);

    const handleToggleWorker = (workerId: number, enabled: boolean) => {
        router.post(
            toggleWorker.url({ library: library.id }),
            {
                worker_id: workerId,
                enabled,
            },
            { preserveScroll: true },
        );
    };

    const handleScan = () => {
        router.post(`/libraries/${library.id}/scan`);
    };

    const handleDelete = () => {
        if (
            !confirm(
                'Delete this library and its job configurations? Executions will be preserved.',
            )
        ) {
            return;
        }

        setIsDeleting(true);
        router.delete(`/libraries/${library.id}`);
    };

    const executionColumns: Column<Execution>[] = [
        {
            key: 'file_path',
            label: 'File',
            render: (e) => (
                <span className="block max-w-60 truncate">{e.file_path}</span>
            ),
        },
        {
            key: 'status',
            label: 'Status',
            render: (e) => <StatusBadge status={e.status} />,
        },
        {
            key: 'created_at',
            label: 'Created',
            render: (e) => new Date(e.created_at).toLocaleString(),
        },
    ];

    return (
        <>
            <Head title={library.base_path} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/libraries">
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <div className="flex-1">
                        <h1 className="truncate text-2xl font-bold">
                            {library.base_path}
                        </h1>
                        <div className="mt-1">
                            <StatusBadge status={library.status} />
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" onClick={handleScan}>
                            <Play className="mr-1 size-4" />
                            Scan Now
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={handleDelete}
                            disabled={isDeleting}
                        >
                            <Trash2 className="mr-1 size-4" />
                            Delete
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Configuration</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div>
                                <span className="text-sm text-muted-foreground">
                                    Scan Interval
                                </span>
                                <p className="font-medium">
                                    {library.scan_interval}s
                                </p>
                            </div>
                            <div>
                                <span className="text-sm text-muted-foreground">
                                    Last Scan
                                </span>
                                <p className="font-medium">
                                    {library.last_scan ?? 'Never'}
                                </p>
                            </div>
                            <div>
                                <span className="text-sm text-muted-foreground">
                                    Enabled Workers
                                </span>
                                <p className="font-medium">
                                    {library.workers?.length ?? 0}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Enabled Workers</CardTitle>
                            <CardDescription>
                                Select which workers are active for this
                                library
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {allWorkers.length === 0 && (
                                <p className="text-sm text-muted-foreground">
                                    No workers configured. Create one in the
                                    Workers tab first.
                                </p>
                            )}
                            {allWorkers.map((worker) => {
                                const enabled = (library.workers ?? []).some(
                                    (w: Worker) => w.id === worker.id,
                                );

                                return (
                                    <div
                                        key={worker.id}
                                        className="flex items-center justify-between"
                                    >
                                        <div className="flex flex-col">
                                            <span className="text-sm font-medium">
                                                {worker.name}
                                            </span>
                                            <span className="text-xs text-muted-foreground">
                                                {worker.job_type
                                                    ? JOB_TYPE_LABELS[
                                                          worker.job_type
                                                      ] ?? worker.job_type
                                                    : '-'}
                                            </span>
                                        </div>
                                        <Switch
                                            checked={enabled}
                                            onCheckedChange={(checked) =>
                                                handleToggleWorker(
                                                    worker.id,
                                                    checked,
                                                )
                                            }
                                        />
                                    </div>
                                );
                            })}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Recent Executions</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <DataTable
                            columns={executionColumns}
                            data={recentExecutions}
                            emptyMessage="No executions yet."
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

LibraryDetail.layout = (page: React.ReactNode) => (
    <AppLayout
        breadcrumbs={[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Libraries', href: '/libraries' },
            { title: 'Detail', href: '#' },
        ]}
    >
        {page}
    </AppLayout>
);
