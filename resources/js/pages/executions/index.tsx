import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { RotateCcw, XCircle, Play, Square, Pause, Trash2 } from 'lucide-react';
import { DataTable } from '@/components/data-table';
import type { Column } from '@/components/data-table';
import { FilterBar } from '@/components/filter-bar';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/app-layout';
import {
    retry,
    cancel,
    start,
    pause,
    resume,
    stop,
    destroy,
    batchStart,
    batchPause,
    batchResume,
    batchStop,
    batchDelete,
} from '@/actions/App/Http/Controllers/ExecutionsController';

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

const lifecycleConfirm = (action: string, count: number) =>
    confirm(`${action} ${count} execution(s)?`);

export default function ExecutionsIndex({
    executions,
    filters,
    statuses,
}: {
    executions: Pagination;
    filters: Record<string, string>;
    statuses: { value: string; label: string }[];
}) {
    const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());

    const toggleSelect = (id: number) => {
        const next = new Set(selectedIds);
        if (next.has(id)) {
            next.delete(id);
        } else {
            next.add(id);
        }
        setSelectedIds(next);
    };

    const toggleSelectAll = () => {
        if (selectedIds.size === executions.data.length) {
            setSelectedIds(new Set());
        } else {
            setSelectedIds(
                new Set(executions.data.map((e: Execution) => e.id)),
            );
        }
    };

    const batchAction = (
        action: string,
        url: string,
        confirmMsg?: string,
    ) => {
        if (selectedIds.size === 0) return;
        if (confirmMsg && !confirm(confirmMsg)) return;
        router.post(
            url,
            { ids: Array.from(selectedIds) },
            { preserveScroll: true, onFinish: () => setSelectedIds(new Set()) },
        );
    };

    const singleAction = (
        action: string,
        url: string,
        confirmMsg?: string,
    ) => {
        if (confirmMsg && !confirm(confirmMsg)) return;
        router.post(url, {}, { preserveScroll: true });
    };

    const handleRetry = (execution: Execution) => {
        if (!confirm('Retry this execution?')) return;
        router.post(retry.url({ execution: execution.id }), {}, { preserveScroll: true });
    };

    const cancelConfirm = (status: string) =>
        status === 'processing'
            ? 'Abort this running job?'
            : 'Cancel this queued job?';

    const handleCancel = (execution: Execution) => {
        if (!confirm(cancelConfirm(execution.status))) return;
        router.post(cancel.url({ execution: execution.id }), {}, { preserveScroll: true });
    };

    const handleDelete = (execution: Execution) => {
        if (!confirm('Delete this execution record?')) return;
        router.delete(destroy.url({ execution: execution.id }), { preserveScroll: true });
    };

    const columns: Column<Execution>[] = [
        {
            key: 'select',
            label: (
                <Checkbox
                    checked={
                        executions.data.length > 0 &&
                        selectedIds.size === executions.data.length
                    }
                    onCheckedChange={toggleSelectAll}
                />
            ),
            render: (e) => (
                <Checkbox
                    checked={selectedIds.has(e.id)}
                    onCheckedChange={() => toggleSelect(e.id)}
                />
            ),
        },
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
                    {(e.status === 'queued' || e.status === 'paused') && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                singleAction(
                                    'Start',
                                    start.url({ execution: e.id }),
                                )
                            }
                            title="Start"
                        >
                            <Play className="size-3" />
                        </Button>
                    )}
                    {e.status === 'processing' && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                singleAction(
                                    'Pause',
                                    pause.url({ execution: e.id }),
                                )
                            }
                            title="Pause"
                        >
                            <Pause className="size-3" />
                        </Button>
                    )}
                    {e.status === 'paused' && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                singleAction(
                                    'Resume',
                                    resume.url({ execution: e.id }),
                                )
                            }
                            title="Resume"
                        >
                            <RotateCcw className="size-3" />
                        </Button>
                    )}
                    {e.status === 'failed' && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => handleRetry(e)}
                            title="Retry"
                        >
                            <RotateCcw className="size-3" />
                        </Button>
                    )}
                    {(e.status === 'queued' ||
                        e.status === 'processing' ||
                        e.status === 'paused') && (
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() =>
                                singleAction(
                                    'Stop',
                                    stop.url({ execution: e.id }),
                                    cancelConfirm(e.status),
                                )
                            }
                            title="Stop"
                        >
                            <Square className="size-3" />
                        </Button>
                    )}
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() => handleDelete(e)}
                        title="Delete"
                    >
                        <Trash2 className="size-3" />
                    </Button>
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

                {selectedIds.size > 0 && (
                    <div className="flex items-center gap-2 rounded-md border bg-muted/50 px-3 py-2">
                        <span className="text-sm text-muted-foreground">
                            {selectedIds.size} selected
                        </span>
                        <div className="ml-auto flex gap-1">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    batchAction(
                                        'Start',
                                        batchStart.url(),
                                        lifecycleConfirm(
                                            'Start',
                                            selectedIds.size,
                                        ),
                                    )
                                }
                            >
                                <Play className="mr-1 size-3" />
                                Start
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    batchAction(
                                        'Pause',
                                        batchPause.url(),
                                        lifecycleConfirm(
                                            'Pause',
                                            selectedIds.size,
                                        ),
                                    )
                                }
                            >
                                <Pause className="mr-1 size-3" />
                                Pause
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    batchAction(
                                        'Resume',
                                        batchResume.url(),
                                        lifecycleConfirm(
                                            'Resume',
                                            selectedIds.size,
                                        ),
                                    )
                                }
                            >
                                <RotateCcw className="mr-1 size-3" />
                                Resume
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    batchAction(
                                        'Stop',
                                        batchStop.url(),
                                        lifecycleConfirm(
                                            'Stop',
                                            selectedIds.size,
                                        ),
                                    )
                                }
                            >
                                <Square className="mr-1 size-3" />
                                Stop
                            </Button>
                            <Button
                                variant="destructive"
                                size="sm"
                                onClick={() =>
                                    batchAction(
                                        'Delete',
                                        batchDelete.url(),
                                        `Delete ${selectedIds.size} execution(s)?`,
                                    )
                                }
                            >
                                <Trash2 className="mr-1 size-3" />
                                Delete
                            </Button>
                        </div>
                    </div>
                )}

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
                                onClick={() =>
                                    link.url && router.get(link.url)
                                }
                                dangerouslySetInnerHTML={{
                                    __html: link.label,
                                }}
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
