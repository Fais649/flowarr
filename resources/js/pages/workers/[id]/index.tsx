import { Head, router } from '@inertiajs/react';
import { ArrowLeft, Info } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { type Worker, JobTypeLabels } from '@/types/models';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { update } from '@/actions/App/Http/Controllers/WorkersController';

function getTooltipText(jobType: string | null | undefined, setting: 'enabled' | 'concurrency' | 'replace_original'): string {
    const tooltips = {
        transcode_media: {
            enabled: `Enable or disable video transcoding. When disabled, no videos will be transcoded.`,
            concurrency: `Number of videos to transcode simultaneously. Higher values process more videos in parallel but use more system resources.`,
            replace_original: `Replace original video files with transcoded versions. When enabled, the original file is deleted after successful transcoding.`,
        },
        extract_subs: {
            enabled: `Enable or disable subtitle extraction. When disabled, no subtitles will be extracted from videos.`,
            concurrency: `Number of subtitle extractions to run simultaneously. Higher values process more videos in parallel but use more system resources.`,
            replace_original: `Remove embedded subtitles from video files after extraction. When enabled, embedded subtitles are stripped from the original video.`,
        },
        convert_sub: {
            enabled: `Enable or disable subtitle format conversion. When disabled, no subtitle files will be converted to SRT format.`,
            concurrency: `Number of subtitle conversions to run simultaneously. Higher values process more files in parallel but use more system resources.`,
            replace_original: `Delete original subtitle files after conversion to SRT. When enabled, only the converted SRT file is kept.`,
        },
    };

    const defaultTooltips = {
        enabled: `Enable or disable this worker. When disabled, no jobs of this type will be processed.`,
        concurrency: `Number of jobs to process simultaneously. Higher values process more items in parallel but use more system resources.`,
        replace_original: `Replace original files with processed versions. When enabled, original files are deleted after successful processing.`,
    };

    if (jobType && tooltips[jobType as keyof typeof tooltips]) {
        return tooltips[jobType as keyof typeof tooltips][setting];
    }

    return defaultTooltips[setting];
}

export default function WorkerDetail({ worker }: { worker: Worker }) {
    const handleUpdate = (data: Record<string, unknown>) => {
        router.patch(
            update.url({ worker: worker.id }),
            data,
            { preserveScroll: true },
        );
    };

    return (
        <TooltipProvider>
            <Head title={worker.name} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <a href="/workers">
                            <ArrowLeft className="size-4" />
                        </a>
                    </Button>
                    <h1 className="text-2xl font-bold">
                        {worker.job_type
                            ? JobTypeLabels[worker.job_type] ?? worker.job_type
                            : worker.name}
                    </h1>
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
                                            {worker.enabled ? 'Worker is active' : 'Worker is disabled'}
                                        </p>
                                    </div>
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <Info className="size-4 text-muted-foreground cursor-help" />
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            {getTooltipText(worker.job_type, 'enabled')}
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
                                            <Info className="size-4 text-muted-foreground cursor-help" />
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            {getTooltipText(worker.job_type, 'concurrency')}
                                        </TooltipContent>
                                    </Tooltip>
                                </div>
                                <p className="text-xs text-muted-foreground">
                                    Number of concurrent processes (1-99)
                                </p>
                                <Input
                                    type="number"
                                    min={1}
                                    max={99}
                                    defaultValue={worker.concurrency}
                                    disabled={!worker.enabled}
                                    onBlur={(e) => {
                                        const val = Number(e.target.value);
                                        if (
                                            val >= 1 &&
                                            val <= 99 &&
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
                                            Replace original files with processed versions
                                        </p>
                                    </div>
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <Info className="size-4 text-muted-foreground cursor-help" />
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            {getTooltipText(worker.job_type, 'replace_original')}
                                        </TooltipContent>
                                    </Tooltip>
                                </div>
                                <Switch
                                    checked={worker.replace_original}
                                    disabled={!worker.enabled}
                                    onCheckedChange={(checked) =>
                                        handleUpdate({ replace_original: checked })
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
                                        ? JobTypeLabels[worker.job_type] ?? worker.job_type
                                        : '-'}
                                </p>
                            </div>
                            <div>
                                <span className="text-sm text-muted-foreground">
                                    Registered
                                </span>
                                <p className="font-medium">
                                    {new Date(worker.created_at).toLocaleString()}
                                </p>
                            </div>
                            <div>
                                <span className="text-sm text-muted-foreground">
                                    Last Updated
                                </span>
                                <p className="font-medium">
                                    {new Date(worker.updated_at).toLocaleString()}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </TooltipProvider>
    );
}

WorkerDetail.layout = (page: React.ReactNode) => (
    <AppLayout
        breadcrumbs={[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Workers', href: '/workers' },
            { title: 'Detail', href: '#' },
        ]}
    >
        {page}
    </AppLayout>
);
