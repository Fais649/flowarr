import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Info, Pause, Play, Square } from 'lucide-react';
import {
    pause,
    start,
    stop,
    update,
} from '@/actions/App/Http/Controllers/WorkersController';
import { DataTable } from '@/components/data-table';
import type { Column } from '@/components/data-table';
import { DateText } from '@/components/date-text';
import { ExecutionStatusCell } from '@/components/execution-status';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/app-layout';
import { getTooltipText } from '@/lib/worker-tooltips';
import { dashboard } from '@/routes';
import { show as showExecution } from '@/routes/executions';
import { index } from '@/routes/workers';
import { JobTypeLabels } from '@/types/models';
import type { Execution, Worker } from '@/types/models';

export default function WorkerDetail({
    worker,
    maxConcurrency,
    recentExecutions,
}: {
    worker: Worker;
    maxConcurrency: number;
    recentExecutions: Execution[];
}) {
    const handleUpdate = (data: Record<string, string | number | boolean>) => {
        router.patch(update.url({ worker: worker.id }), data, {
            preserveScroll: true,
        });
    };

    const post = (url: string, message?: string) => {
        if (message && !confirm(message)) {
            return;
        }

        router.post(url, {}, { preserveScroll: true });
    };

    const executionColumns: Column<Execution>[] = [
        {
            key: 'file_path',
            label: 'File',
            render: (e) => (
                <Link
                    href={showExecution(e.id)}
                    className="block max-w-md truncate hover:underline"
                >
                    {e.file_path}
                </Link>
            ),
        },
        {
            key: 'status',
            label: 'Status',
            render: (e) => (
                <ExecutionStatusCell
                    status={e.status}
                    progress={e.progress}
                    message={e.message}
                />
            ),
        },
        {
            key: 'created_at',
            label: 'Created',
            render: (e) => <DateText value={e.created_at} />,
        },
    ];

    return (
        <>
            <Head title={worker.name} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href={index()}>
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <h1 className="flex-1 text-2xl font-bold">
                        {worker.job_type
                            ? (JobTypeLabels[worker.job_type] ??
                              worker.job_type)
                            : worker.name}
                    </h1>
                    <div className="flex flex-wrap gap-2">
                        <Button
                            variant="outline"
                            onClick={() =>
                                post(start.url({ worker: worker.id }))
                            }
                            title="Resume paused and retry stopped/failed executions of this type"
                        >
                            <Play className="mr-1 size-4" />
                            Start
                        </Button>
                        <Button
                            variant="outline"
                            onClick={() =>
                                post(pause.url({ worker: worker.id }))
                            }
                        >
                            <Pause className="mr-1 size-4" />
                            Pause
                        </Button>
                        <Button
                            variant="outline"
                            onClick={() =>
                                post(
                                    stop.url({ worker: worker.id }),
                                    'Stop all queued and running executions of this type?',
                                )
                            }
                        >
                            <Square className="mr-1 size-4" />
                            Stop
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Worker Settings</CardTitle>
                            <CardDescription>
                                Configure this worker's behavior
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <div>
                                        <Label>Enabled</Label>
                                        <p className="text-xs text-muted-foreground">
                                            {worker.enabled
                                                ? 'Worker is active'
                                                : 'Worker is disabled'}
                                        </p>
                                    </div>
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <Info className="size-4 cursor-help text-muted-foreground" />
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            {getTooltipText(
                                                worker.job_type,
                                                'enabled',
                                            )}
                                        </TooltipContent>
                                    </Tooltip>
                                </div>
                                <Switch
                                    checked={worker.enabled}
                                    onCheckedChange={(checked) =>
                                        handleUpdate({ enabled: checked })
                                    }
                                />
                            </div>

                            <div className="space-y-2">
                                <div className="flex items-center gap-2">
                                    <Label>Concurrency</Label>
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <Info className="size-4 cursor-help text-muted-foreground" />
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            {getTooltipText(
                                                worker.job_type,
                                                'concurrency',
                                            )}
                                        </TooltipContent>
                                    </Tooltip>
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Number of concurrent processes (1-
                                    {maxConcurrency})
                                </p>
                                <Input
                                    key={worker.concurrency}
                                    type="number"
                                    min={1}
                                    max={maxConcurrency}
                                    defaultValue={worker.concurrency}
                                    disabled={!worker.enabled}
                                    onBlur={(e) => {
                                        const val = Number(e.target.value);

                                        if (
                                            val >= 1 &&
                                            val <= maxConcurrency &&
                                            val !== worker.concurrency
                                        ) {
                                            handleUpdate({ concurrency: val });
                                        }
                                    }}
                                />
                            </div>

                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    <div>
                                        <Label>Replace Original</Label>
                                        <p className="text-xs text-muted-foreground">
                                            Replace original files with
                                            processed versions
                                        </p>
                                    </div>
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <Info className="size-4 cursor-help text-muted-foreground" />
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            {getTooltipText(
                                                worker.job_type,
                                                'replace_original',
                                            )}
                                        </TooltipContent>
                                    </Tooltip>
                                </div>
                                <Switch
                                    checked={worker.replace_original}
                                    disabled={!worker.enabled}
                                    onCheckedChange={(checked) =>
                                        handleUpdate({
                                            replace_original: checked,
                                        })
                                    }
                                />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Worker Info</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <span className="text-sm text-muted-foreground">
                                    Job Type
                                </span>
                                <p className="font-medium">
                                    {worker.job_type
                                        ? (JobTypeLabels[worker.job_type] ??
                                          worker.job_type)
                                        : '-'}
                                </p>
                            </div>
                            <div>
                                <span className="text-sm text-muted-foreground">
                                    Activity
                                </span>
                                <p className="font-medium">
                                    {worker.processing_count ?? 0} running,{' '}
                                    {worker.queued_count ?? 0} queued
                                </p>
                            </div>
                            <div>
                                <span className="text-sm text-muted-foreground">
                                    Queue worker processes
                                </span>
                                <p className="font-medium">
                                    {worker.running_processes === null ||
                                    worker.running_processes === undefined
                                        ? 'Unknown (supervisord not reachable)'
                                        : `${worker.running_processes} running`}
                                </p>
                            </div>
                            <div>
                                <span className="text-sm text-muted-foreground">
                                    Registered
                                </span>
                                <p className="font-medium">
                                    <DateText value={worker.created_at} />
                                </p>
                            </div>
                            <div>
                                <span className="text-sm text-muted-foreground">
                                    Last Updated
                                </span>
                                <p className="font-medium">
                                    <DateText value={worker.updated_at} />
                                </p>
                            </div>
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
                            emptyMessage="No executions for this job type yet."
                        />
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

WorkerDetail.layout = (page: React.ReactNode) => (
    <AppLayout
        breadcrumbs={[
            { title: 'Dashboard', href: dashboard() },
            { title: 'Workers', href: index() },
            { title: 'Detail', href: '#' },
        ]}
    >
        {page}
    </AppLayout>
);
