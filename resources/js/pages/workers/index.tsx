import { Head, router } from '@inertiajs/react';
import { Info } from 'lucide-react';
import { update } from '@/actions/App/Http/Controllers/WorkersController';
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

function getTooltipText(jobType: string | null | undefined, setting: 'enabled' | 'concurrency' | 'replace_original'): string {
    const jobLabel = jobType ? JobTypeLabels[jobType] ?? jobType : 'worker';

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
        enabled: `Enable or disable this ${jobLabel.toLowerCase()}. When disabled, no jobs of this type will be processed.`,
        concurrency: `Number of jobs to process simultaneously. Higher values process more items in parallel but use more system resources.`,
        replace_original: `Replace original files with processed versions. When enabled, original files are deleted after successful processing.`,
    };

    if (jobType && tooltips[jobType as keyof typeof tooltips]) {
        return tooltips[jobType as keyof typeof tooltips][setting];
    }

    return defaultTooltips[setting];
}

export default function WorkersIndex({ workers }: { workers: Worker[] }) {
    const handleUpdate = (worker: Worker, data: Record<string, unknown>) => {
        router.patch(
            update.url({ worker: worker.id }),
            data,
            { preserveScroll: true },
        );
    };

    return (
        <TooltipProvider>
            <Head title="Workers" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <h1 className="text-2xl font-bold">Workers</h1>

                <div className="grid gap-4 md:grid-cols-3">
                    {workers.map((worker) => (
                        <Card key={worker.id}>
                            <CardHeader className="pb-3">
                                <div className="flex items-center justify-between">
                                    <CardTitle className="text-base">
                                        {worker.job_type
                                            ? JobTypeLabels[worker.job_type] ?? worker.job_type
                                            : worker.name}
                                    </CardTitle>
                                    <div className="flex items-center gap-2">
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Info className="size-4 text-muted-foreground cursor-help" />
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                {getTooltipText(worker.job_type, 'enabled')}
                                            </TooltipContent>
                                        </Tooltip>
                                        <Switch
                                            checked={worker.enabled}
                                            onCheckedChange={(checked) =>
                                                handleUpdate(worker, { enabled: checked })
                                            }
                                        />
                                    </div>
                                </div>
                                <CardDescription>
                                    {worker.enabled ? 'Active' : 'Disabled'}
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
                                                <Info className="size-3 text-muted-foreground cursor-help" />
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                {getTooltipText(worker.job_type, 'concurrency')}
                                            </TooltipContent>
                                        </Tooltip>
                                    </div>
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
                                                handleUpdate(worker, { concurrency: val });
                                            }
                                        }}
                                    />
                                </div>
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-2">
                                        <Label
                                            htmlFor={`replace-${worker.id}`}
                                            className="text-xs text-muted-foreground cursor-pointer"
                                        >
                                            Replace Original
                                        </Label>
                                        <Tooltip>
                                            <TooltipTrigger asChild>
                                                <Info className="size-3 text-muted-foreground cursor-help" />
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                {getTooltipText(worker.job_type, 'replace_original')}
                                            </TooltipContent>
                                        </Tooltip>
                                    </div>
                                    <Switch
                                        id={`replace-${worker.id}`}
                                        checked={worker.replace_original}
                                        disabled={!worker.enabled}
                                        onCheckedChange={(checked) =>
                                            handleUpdate(worker, { replace_original: checked })
                                        }
                                    />
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </TooltipProvider>
    );
}

WorkersIndex.layout = (page: React.ReactNode) => (
    <AppLayout
        breadcrumbs={[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Workers', href: '/workers' },
        ]}
    >
        {page}
    </AppLayout>
);
