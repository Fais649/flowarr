import { Head, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import Heading from '@/components/heading';
import { SettingsNav } from '@/components/settings-nav';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import {
    edit as editWorkerSettings,
    update as updateWorkerSettings,
} from '@/routes/workers';

type Props = {
    concurrency: {
        transcode_media: number;
        extract_subs: number;
        convert_sub: number;
    };
    paused: boolean;
};

const jobTypes = [
    {
        key: 'transcode_media',
        label: 'Transcode Media',
        description: 'HEVC video transcoding jobs',
    },
    {
        key: 'extract_subs',
        label: 'Extract Subtitles',
        description: 'Subtitle extraction from media files',
    },
    {
        key: 'convert_sub',
        label: 'Convert Subtitles',
        description: 'Subtitle format conversion jobs',
    },
] as const;

export default function Workers({ concurrency, paused }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        concurrency: {
            transcode_media: concurrency.transcode_media,
            extract_subs: concurrency.extract_subs,
            convert_sub: concurrency.convert_sub,
        },
        paused,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(updateWorkerSettings.url(), {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Worker settings" />

            <SettingsNav currentPath={window.location.pathname} />

            <h1 className="sr-only">Worker settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Worker settings"
                    description="Configure job concurrency limits and global pause"
                />

                <form onSubmit={submit} className="space-y-6">
                    <div className="space-y-4">
                        {jobTypes.map((job) => (
                            <div key={job.key} className="grid gap-2">
                                <Label htmlFor={job.key}>{job.label}</Label>
                                <p className="text-sm text-muted-foreground">
                                    {job.description}
                                </p>
                                <Input
                                    id={job.key}
                                    type="number"
                                    min={1}
                                    className="mt-1 block w-full max-w-xs"
                                    value={data.concurrency[job.key]}
                                    onChange={(e) =>
                                        setData(
                                            `concurrency.${job.key}`,
                                            Number(e.target.value),
                                        )
                                    }
                                />
                                <InputError
                                    className="mt-2"
                                    message={
                                        errors[
                                            `concurrency.${job.key}` as keyof typeof errors
                                        ]
                                    }
                                />
                            </div>
                        ))}
                    </div>

                    <div className="flex items-center gap-2">
                        <Switch
                            id="paused"
                            checked={data.paused}
                            onCheckedChange={(checked: boolean) =>
                                setData('paused', checked)
                            }
                        />
                        <Label htmlFor="paused">
                            Pause all media processing
                        </Label>
                    </div>

                    <InputError className="mt-2" message={errors.paused} />

                    <div className="flex items-center gap-4">
                        <Button
                            disabled={processing}
                            data-test="update-worker-settings-button"
                        >
                            Save
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

Workers.layout = {
    breadcrumbs: [
        {
            title: 'Worker settings',
            href: editWorkerSettings(),
        },
    ],
};
