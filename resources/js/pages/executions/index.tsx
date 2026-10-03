import { Head, Link, router, usePoll } from '@inertiajs/react';
import { Pause, Play, RotateCcw, Square, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import {
    batchDelete,
    batchPause,
    batchResume,
    batchRetry,
    batchStop,
} from '@/actions/App/Http/Controllers/ExecutionsController';
import { DataTable } from '@/components/data-table';
import type { Column } from '@/components/data-table';
import { DateText } from '@/components/date-text';
import { ExecutionActions } from '@/components/execution-actions';
import { ExecutionStatusCell } from '@/components/execution-status';
import { FilterBar } from '@/components/filter-bar';
import type { PickerLibrary } from '@/components/manual-execution-picker';
import ManualExecutionPicker from '@/components/manual-execution-picker';
import { ProcessingBanner } from '@/components/processing-banner';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { index, show } from '@/routes/executions';
import type { Execution } from '@/types/models';
import { ACTIVE_STATUSES, JobTypeLabels } from '@/types/models';

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
    `${action} ${count} execution(s)?`;

export default function ExecutionsIndex({
    executions,
    filters,
    statuses,
    libraries,
}: {
    executions: Pagination;
    filters: Record<string, string>;
    statuses: { value: string; label: string }[];
    libraries: PickerLibrary[];
}) {
    const [selectedIds, setSelectedIds] = useState<Set<number>>(new Set());
    const [search, setSearch] = useState(filters.search ?? '');
    const hasActive = executions.data.some((e) =>
        ACTIVE_STATUSES.includes(e.status),
    );

    const { start: startPolling, stop: stopPolling } = usePoll(
        3000,
        { only: ['executions'] },
        { autoStart: false },
    );

    useEffect(() => {
        if (hasActive) {
            startPolling();
        } else {
            stopPolling();
        }
    }, [hasActive, startPolling, stopPolling]);

    useEffect(() => {
        if (search === (filters.search ?? '')) {
            return;
        }

        const timeout = setTimeout(() => {
            const params = new URLSearchParams(window.location.search);

            if (search) {
                params.set('search', search);
            } else {
                params.delete('search');
            }

            params.delete('page');
            router.get(
                `${window.location.pathname}?${params.toString()}`,
                {},
                { preserveState: true, preserveScroll: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, filters.search]);

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
            setSelectedIds(new Set(executions.data.map((e) => e.id)));
        }
    };

    const batchAction = (url: string, confirmMsg?: string) => {
        if (selectedIds.size === 0) {
            return;
        }

        if (confirmMsg && !confirm(confirmMsg)) {
            return;
        }

        router.post(
            url,
            { ids: Array.from(selectedIds) },
            { preserveScroll: true, onFinish: () => setSelectedIds(new Set()) },
        );
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
                    aria-label="Select all"
                />
            ) as unknown as string,
            render: (e) => (
                <Checkbox
                    checked={selectedIds.has(e.id)}
                    onCheckedChange={() => toggleSelect(e.id)}
                    aria-label={`Select execution ${e.id}`}
                />
            ),
        },
        {
            key: 'file_path',
            label: 'File',
            render: (e) => (
                <Link
                    href={show(e.id)}
                    className="block max-w-xs truncate hover:underline"
                    title={e.file_path}
                >
                    {e.file_path}
                </Link>
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
            render: (e) =>
                JobTypeLabels[e.library_job?.job_id] ??
                e.library_job?.job_id ??
                '-',
        },
        {
            key: 'status',
            label: 'Status',
            render: (e) => (
                <div className="space-y-1">
                    <ExecutionStatusCell
                        status={e.status}
                        progress={e.progress}
                        message={e.status === 'completed' ? null : e.message}
                    />
                    {e.replacement_status && (
                        <p className="text-xs text-muted-foreground">
                            Original: {e.replacement_status}
                        </p>
                    )}
                    {e.status === 'completed' && e.message && (
                        <p
                            className="max-w-56 truncate text-xs text-muted-foreground"
                            title={e.message}
                        >
                            {e.message}
                        </p>
                    )}
                </div>
            ),
        },
        {
            key: 'created_at',
            label: 'Created',
            render: (e) => <DateText value={e.created_at} />,
        },
        {
            key: 'actions',
            label: '',
            render: (e) => <ExecutionActions execution={e} />,
        },
    ];

    return (
        <>
            <Head title="Executions" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-baseline justify-between">
                    <h1 className="text-2xl font-bold">Executions</h1>
                    <ManualExecutionPicker libraries={libraries} />
                    <span className="text-sm text-muted-foreground">
                        {executions.total} total
                    </span>
                </div>

                <ProcessingBanner />

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
                        {
                            key: 'library_id',
                            label: 'Library',
                            value: filters.library_id ?? 'all',
                            options: [
                                { value: 'all', label: 'All' },
                                ...libraries.map((l) => ({
                                    value: String(l.id),
                                    label: l.base_path,
                                })),
                            ],
                        },
                    ]}
                    search={{
                        value: search,
                        placeholder: 'Search file path…',
                        onChange: setSearch,
                    }}
                />

                {selectedIds.size > 0 && (
                    <div className="flex flex-wrap items-center gap-2 rounded-md border bg-muted/50 px-3 py-2">
                        <span className="text-sm text-muted-foreground">
                            {selectedIds.size} selected
                        </span>
                        <div className="ml-auto flex flex-wrap gap-1">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    batchAction(
                                        batchRetry.url(),
                                        lifecycleConfirm(
                                            'Retry',
                                            selectedIds.size,
                                        ),
                                    )
                                }
                            >
                                <RotateCcw className="mr-1 size-3" />
                                Retry
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => batchAction(batchPause.url())}
                            >
                                <Pause className="mr-1 size-3" />
                                Pause
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => batchAction(batchResume.url())}
                            >
                                <Play className="mr-1 size-3" />
                                Resume
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() =>
                                    batchAction(
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
                    <div className="mt-4 flex flex-wrap items-center justify-center gap-2">
                        {executions.links.map((link) => (
                            <Button
                                key={link.label}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                onClick={() =>
                                    link.url &&
                                    router.get(
                                        link.url,
                                        {},
                                        { preserveScroll: true },
                                    )
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
            { title: 'Dashboard', href: dashboard() },
            { title: 'Executions', href: index() },
        ]}
    >
        {page}
    </AppLayout>
);
