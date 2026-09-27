import { Deferred, Head, Link, router, usePoll } from '@inertiajs/react';
import { Cpu, Info, Pause, Play, RefreshCw, Square, Tv, X } from 'lucide-react';
import {
    clearStreams,
    pauseAll,
    refreshCapabilities,
    startAll,
    stopAll,
    update,
} from '@/actions/App/Http/Controllers/WorkersController';
import { describePauseReasons } from '@/components/processing-banner';
import { Badge } from '@/components/ui/badge';
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
import { Skeleton } from '@/components/ui/skeleton';
import { Switch } from '@/components/ui/switch';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import AppLayout from '@/layouts/app-layout';
import { getTooltipText } from '@/lib/worker-tooltips';
import { dashboard } from '@/routes';
import { edit as processingSettings } from '@/routes/config/processing';
import { index, show } from '@/routes/workers';
import type {
    HardwareCapabilities,
    ProcessingState,
    Worker,
} from '@/types/models';
import { JobTypeLabels } from '@/types/models';

type Stream = {
    source: string;
    title: string | null;
    started_at: number;
    seen_at: number;
};

const encoderLabels: Record<string, string> = {
    hevc_nvenc: 'NVIDIA NVENC',
    hevc_vaapi: 'VAAPI (AMD / Intel)',
    libx265: 'libx265 (CPU)',
};

function ProcessingControls({
    processing,
    streams,
}: {
    processing: ProcessingState;
    streams: Stream[];
}) {
    const post = (url: string, message?: string) => {
        if (message && !confirm(message)) {
            return;
        }

        router.post(url, {}, { preserveScroll: true });
    };

    return (
        <Card>
            <CardHeader>
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <CardTitle className="flex items-center gap-2">
                            Processing
                            <Badge
                                variant="outline"
                                className={
                                    processing.paused
                                        ? 'border-orange-300 bg-orange-100 text-orange-800 dark:border-orange-800 dark:bg-orange-900/30 dark:text-orange-400'
                                        : 'border-green-300 bg-green-100 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-400'
                                }
                            >
                                {processing.paused ? 'On hold' : 'Running'}
                            </Badge>
                        </CardTitle>
                        <CardDescription className="mt-1">
                            {processing.paused
                                ? describePauseReasons(processing).join(' ')
                                : 'Workers pick up queued executions as soon as they are free.'}
                        </CardDescription>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {processing.manual ? (
                            <Button
                                variant="outline"
                                onClick={() => post(startAll.url())}
                            >
                                <Play className="mr-1 size-4" />
                                Resume all
                            </Button>
                        ) : (
                            <Button
                                variant="outline"
                                onClick={() => post(pauseAll.url())}
                            >
                                <Pause className="mr-1 size-4" />
                                Pause all
                            </Button>
                        )}
                        <Button
                            variant="outline"
                            onClick={() =>
                                post(
                                    stopAll.url(),
                                    'Stop every queued and running execution?',
                                )
                            }
                        >
                            <Square className="mr-1 size-4" />
                            Stop all
                        </Button>
                    </div>
                </div>
            </CardHeader>
            <CardContent className="space-y-3 text-sm">
                <div className="flex flex-wrap gap-x-6 gap-y-1 text-muted-foreground">
                    <span>
                        Processing window:{' '}
                        <span className="font-medium text-foreground">
                            {processing.window
                                ? `${processing.window.start}–${processing.window.end}`
                                : 'Always'}
                        </span>
                    </span>
                    <Link
                        href={processingSettings()}
                        className="underline-offset-4 hover:underline"
                    >
                        Configure schedule & notifications
                    </Link>
                </div>

                {streams.length > 0 && (
                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <span className="flex items-center gap-2 font-medium">
                                <Tv className="size-4" />
                                Active streams
                            </span>
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() =>
                                    router.delete(clearStreams.url(), {
                                        preserveScroll: true,
                                    })
                                }
                                title="Use when a media server missed a stop event"
                            >
                                <X className="mr-1 size-3" />
                                Clear
                            </Button>
                        </div>
                        <ul className="space-y-1">
                            {streams.map((stream) => (
                                <li
                                    key={`${stream.source}-${stream.started_at}-${stream.title}`}
                                    className="flex items-center justify-between rounded-md border px-3 py-1.5"
                                >
                                    <span className="truncate">
                                        {stream.title ?? 'Unknown item'}
                                    </span>
                                    <span className="text-xs text-muted-foreground capitalize">
                                        {stream.source} · since{' '}
                                        {new Date(
                                            stream.started_at * 1000,
                                        ).toLocaleTimeString()}
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

function CapabilitiesCard({
    capabilities,
}: {
    capabilities?: HardwareCapabilities;
}) {
    return (
        <Card>
            <CardHeader>
                <div className="flex items-center justify-between gap-2">
                    <div>
                        <CardTitle className="flex items-center gap-2">
                            <Cpu className="size-4" />
                            Hardware acceleration
                        </CardTitle>
                        <CardDescription className="mt-1">
                            Encoders and GPUs available to the transcoder.
                        </CardDescription>
                    </div>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={() =>
                            router.post(
                                refreshCapabilities.url(),
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        <RefreshCw className="mr-1 size-3" />
                        Re-detect
                    </Button>
                </div>
            </CardHeader>
            <CardContent>
                <Deferred
                    data="capabilities"
                    fallback={
                        <div className="space-y-2">
                            <Skeleton className="h-4 w-1/2" />
                            <Skeleton className="h-4 w-2/3" />
                        </div>
                    }
                >
                    {capabilities && (
                        <div className="space-y-3 text-sm">
                            {capabilities.ffmpeg_version === null ? (
                                <p className="text-destructive">
                                    ffmpeg was not found. Transcoding and
                                    subtitle jobs will fail.
                                </p>
                            ) : (
                                <p>
                                    Transcodes use{' '}
                                    <span className="font-medium">
                                        {encoderLabels[
                                            capabilities.selected_encoder ?? ''
                                        ] ?? capabilities.selected_encoder}
                                    </span>{' '}
                                    <span className="text-muted-foreground">
                                        (mode: {capabilities.mode}, ffmpeg{' '}
                                        {capabilities.ffmpeg_version})
                                    </span>
                                </p>
                            )}
                            <div className="flex flex-wrap gap-2">
                                {Object.entries(capabilities.encoders).map(
                                    ([encoder, available]) => (
                                        <Badge
                                            key={encoder}
                                            variant={
                                                available
                                                    ? 'secondary'
                                                    : 'outline'
                                            }
                                            className={
                                                available
                                                    ? ''
                                                    : 'text-muted-foreground line-through'
                                            }
                                        >
                                            {encoderLabels[encoder] ?? encoder}
                                        </Badge>
                                    ),
                                )}
                            </div>
                            <p className="text-xs text-muted-foreground">
                                NVIDIA device:{' '}
                                {capabilities.devices.nvidia
                                    ? 'detected'
                                    : 'not found'}{' '}
                                · VAAPI device ({capabilities.vaapi_device}):{' '}
                                {capabilities.devices.vaapi
                                    ? 'detected'
                                    : 'not found'}
                            </p>
                        </div>
                    )}
                </Deferred>
            </CardContent>
        </Card>
    );
}

export default function WorkersIndex({
    workers,
    maxConcurrency,
    processing,
    streams,
    capabilities,
}: {
    workers: Worker[];
    maxConcurrency: number;
    processing: ProcessingState;
    streams: Stream[];
    capabilities?: HardwareCapabilities;
}) {
    usePoll(5000, { only: ['workers', 'processing', 'streams'] });

    const handleUpdate = (
        worker: Worker,
        data: Record<string, string | number | boolean>,
    ) => {
        router.patch(update.url({ worker: worker.id }), data, {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Workers" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-2xl font-bold">Workers</h1>

                <div className="grid gap-4 lg:grid-cols-2">
                    <ProcessingControls
                        processing={processing}
                        streams={streams}
                    />
                    <CapabilitiesCard capabilities={capabilities} />
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    {workers.map((worker) => (
                        <Card key={worker.id}>
                            <CardHeader className="pb-3">
                                <div className="flex items-center justify-between">
                                    <CardTitle className="text-base">
                                        <Link
                                            href={show(worker.id)}
                                            className="hover:underline"
                                        >
                                            {worker.job_type
                                                ? (JobTypeLabels[
                                                      worker.job_type
                                                  ] ?? worker.job_type)
                                                : worker.name}
                                        </Link>
                                    </CardTitle>
                                    <div className="flex items-center gap-2">
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
                                        <Switch
                                            checked={worker.enabled}
                                            onCheckedChange={(checked) =>
                                                handleUpdate(worker, {
                                                    enabled: checked,
                                                })
                                            }
                                        />
                                    </div>
                                </div>
                                <CardDescription>
                                    {worker.enabled ? 'Active' : 'Disabled'}
                                    {' · '}
                                    {worker.processing_count ?? 0} running,{' '}
                                    {worker.queued_count ?? 0} queued
                                    {worker.running_processes !== null &&
                                        worker.running_processes !==
                                            undefined &&
                                        ` · ${worker.running_processes}/${worker.enabled ? worker.concurrency : 0} processes up`}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <div className="flex items-center gap-2">
                                        <Label className="text-xs text-muted-foreground">
                                            Concurrency
                                        </Label>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Info className="size-3 cursor-help text-muted-foreground" />
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                {getTooltipText(
                                                    worker.job_type,
                                                    'concurrency',
                                                )}
                                            </TooltipContent>
                                        </Tooltip>
                                    </div>
                                    <Input
                                        key={`${worker.id}-${worker.concurrency}`}
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
                                                handleUpdate(worker, {
                                                    concurrency: val,
                                                });
                                            }
                                        }}
                                    />
                                </div>
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <Label
                                            htmlFor={`replace-${worker.id}`}
                                            className="cursor-pointer text-xs text-muted-foreground"
                                        >
                                            Replace Original
                                        </Label>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Info className="size-3 cursor-help text-muted-foreground" />
                                            </TooltipTrigger>
                                            <TooltipContent className="max-w-xs">
                                                {getTooltipText(
                                                    worker.job_type,
                                                    'replace_original',
                                                )}
                                            </TooltipContent>
                                        </Tooltip>
                                    </div>
                                    <Switch
                                        id={`replace-${worker.id}`}
                                        checked={worker.replace_original}
                                        disabled={!worker.enabled}
                                        onCheckedChange={(checked) =>
                                            handleUpdate(worker, {
                                                replace_original: checked,
                                            })
                                        }
                                    />
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </>
    );
}

WorkersIndex.layout = (page: React.ReactNode) => (
    <AppLayout
        breadcrumbs={[
            { title: 'Dashboard', href: dashboard() },
            { title: 'Workers', href: index() },
        ]}
    >
        {page}
    </AppLayout>
);
