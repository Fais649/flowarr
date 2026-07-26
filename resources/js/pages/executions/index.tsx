import { Head, router } from '@inertiajs/react';
import { RotateCcw, XCircle } from 'lucide-react';
import { DataTable } from '@/components/data-table';
import type { Column } from '@/components/data-table';
import { FilterBar } from '@/components/filter-bar';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';

type Execution = {
    id: number;
    file_path: string;
    status: string;
    library_job: {
        id: number;
        job_id: string;
        library: { id: number; base_path: string };
    };
    created_at: string;
    finished_at: string | null;
};

type Pagination = {
    data: Execution[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number;
    to: number;
    links: { url: string | null; label: string; active: boolean }[];
};

export default function ExecutionsIndex({
    executions,
    filters,
    statuses,
}: {
    executions: Pagination;
    filters: Record<string, string>;
    statuses: { value: string; label: string }[];
}) {
    const handleRetry = (execution: Execution) => {
        if (!confirm('Retry this execution?')) {
return;
}

        router.post(`/executions/${execution.id}/retry`);
    };

    const handleCancel = (execution: Execution) => {
        if (!confirm('Cancel this execution?')) {
return;
}

        router.post(`/executions/${execution.id}/cancel`);
    };

    const columns: Column<Execution>[] = [
        {
            key: 'file_path',
            label: 'File',
            render: (e) => (
                <span className="block max-w-xs truncate">{e.file_path}</span>
            ),
        },
        {
            key: 'library',
            label: 'Library',
            render: (e) => e.library_job?.library?.base_path ?? '-',
        },
        {
            key: 'job',
            label: 'Job Type',
            render: (e) => e.library_job?.job_id ?? '-',
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
        {
            key: 'actions',
            label: '',
            render: (e) => (
                <div className="flex justify-end gap-1">
                    {e.status === 'failed' && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handleRetry(e)}
                        >
                            <RotateCcw className="size-3" />
                        </Button>
                    )}
                    {(e.status === 'queued' || e.status === 'processing') && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handleCancel(e)}
                        >
                            <XCircle className="size-3" />
                        </Button>
                    )}
                </div>
            ),
        },
    ];


    return (
        <>
            <Head title="Executions" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-2xl font-bold">Executions</h1>

                <FilterBar
                    filters={[
                        {
                            key: 'status',
                            label: 'Status',
                            value: filters.status ?? 'all',
                            options: [
                                { value: 'all', label: 'All' },
                                ...statuses.map((s) => ({
                                    value: s.value,
                                    label: s.label,
                                })),
                            ],
                        },
                    ]}
                />

                <DataTable
                    columns={columns}
                    data={executions.data}
                    emptyMessage="No executions found."
                />

                {executions.last_page > 1 && (
                    <div className="mt-4 flex items-center justify-center gap-2">
                        {executions.links.map((link, i) => (
                            <Button
                                key={i}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url)}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

ExecutionsIndex.layout = (page: React.ReactNode) => (
    <AppLayout
        breadcrumbs={[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Executions', href: '/executions' },
        ]}
    >
        {page}
    </AppLayout>
);
