import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Play, Trash2 } from 'lucide-react';
import { useState } from 'react';
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

type Library = {
    id: number;
    base_path: string;
    status: string;
    scan_interval: number;
    last_scan: string | null;
    library_jobs: { id: number; job_id: string }[];
};

type Execution = {
    id: number;
    file_path: string;
    status: string;
    library_job: { id: number; job_id: string };
    created_at: string;
};

type JobType = {
    value: string;
    label: string;
};

export default function LibraryDetail({
    library,
    recentExecutions,
    jobTypes,
}: {
    library: Library;
    recentExecutions: Execution[];
    jobTypes: JobType[];
}) {
    const [isDeleting, setIsDeleting] = useState(false);

    const handleToggleJob = (jobId: string, enabled: boolean) => {
        router.post(`/libraries/${library.id}/toggle-job`, {
            job_id: jobId,
            enabled,
        });
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
                                    Enabled Jobs
                                </span>
                                <p className="font-medium">
                                    {library.library_jobs.length}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Job Toggles</CardTitle>
                            <CardDescription>
                                Enable or disable job types for this library
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {jobTypes.map((jobType) => {
                                const enabled = library.library_jobs.some(
                                    (j) => j.job_id === jobType.value,
                                );

                                return (
                                    <div
                                        key={jobType.value}
                                        className="flex items-center justify-between"
                                    >
                                        <span className="text-sm font-medium">
                                            {jobType.label}
                                        </span>
                                        <Switch
                                            checked={enabled}
                                            onCheckedChange={(checked) =>
                                                handleToggleJob(
                                                    jobType.value,
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
