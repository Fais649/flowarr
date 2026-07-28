import { Head, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type Worker, JOB_TYPE_LABELS } from '@/types/models';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import {
    update,
    start,
    pause,
    resume,
    stop,
    destroy,
} from '@/actions/App/Http/Controllers/WorkersController';


export default function WorkerDetail({ worker }: { worker: Worker }) {
    const handleAction = (
        action: string,
        url: string,
        confirmMsg?: string,
    ) => {
        if (confirmMsg && !confirm(confirmMsg)) return;
        router.post(url, {}, { preserveScroll: true });
    };

    return (
        <>
            <Head title={worker.name} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <a href="/workers">
                            <ArrowLeft className="size-4" />
                        </a>
                    </Button>
                    <h1 className="text-2xl font-bold">{worker.name}</h1>
                </div>

                <div className="flex flex-wrap gap-2">
                    <Button
                        variant="default"
                        onClick={() =>
                            handleAction(
                                'Start',
                                start.url({ worker: worker.id }),
                            )
                        }
                    >
                        Start
                    </Button>
                    <Button
                        variant="outline"
                        onClick={() =>
                            handleAction(
                                'Pause',
                                pause.url({ worker: worker.id }),
                            )
                        }
                    >
                        Pause
                    </Button>
                    <Button
                        variant="outline"
                        onClick={() =>
                            handleAction(
                                'Resume',
                                resume.url({ worker: worker.id }),
                            )
                        }
                    >
                        Resume
                    </Button>
                    <Button
                        variant="outline"
                        onClick={() =>
                            handleAction(
                                'Stop',
                                stop.url({ worker: worker.id }),
                            )
                        }
                    >
                        Stop
                    </Button>
                    <Button
                        variant="destructive"
                        onClick={() => {
                            if (
                                confirm(
                                    `Delete worker "${worker.name}"? This cannot be undone.`,
                                )
                            ) {
                                router.delete(
                                    destroy.url({ worker: worker.id }),
                                );
                            }
                        }}
                    >
                        Delete Worker
                    </Button>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Worker Info</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <Label>Name</Label>
                                <Input
                                    defaultValue={worker.name}
                                    onBlur={(e) => {
                                        const val = e.target.value.trim();
                                        if (val && val !== worker.name) {
                                            router.patch(
                                                update.url({
                                                    worker: worker.id,
                                                }),
                                                { name: val },
                                                { preserveScroll: true },
                                            );
                                        }
                                    }}
                                />
                            </div>
                            <div>
                                <Label>Job Type</Label>
                                <p className="font-medium">
                                    {worker.job_type
                                        ? JOB_TYPE_LABELS[worker.job_type] ??
                                        worker.job_type
                                        : '-'}
                                </p>
                            </div>
                            <div>
                                <Label>Concurrency</Label>
                                <Input
                                    type="number"
                                    min={1}
                                    max={99}
                                    defaultValue={worker.concurrency}
                                    onBlur={(e) => {
                                        const val = Number(e.target.value);
                                        if (
                                            val >= 1 &&
                                            val <= 99 &&
                                            val !== worker.concurrency
                                        ) {
                                            router.patch(
                                                update.url({
                                                    worker: worker.id,
                                                }),
                                                { concurrency: val },
                                                { preserveScroll: true },
                                            );
                                        }
                                    }}
                                />
                            </div>
                            <div>
                                <span className="text-sm text-muted-foreground">
                                    Registered
                                </span>
                                <p className="font-medium">
                                    {new Date(
                                        worker.created_at,
                                    ).toLocaleString()}
                                </p>
                            </div>
                            <div>
                                <span className="text-sm text-muted-foreground">
                                    Last Heartbeat
                                </span>
                                <p className="font-medium">
                                    {new Date(
                                        worker.updated_at,
                                    ).toLocaleString()}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

WorkerDetail.layout = (page: React.ReactNode) => (
    <AppLayout
        breadcrumbs={[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Workers', href: '/workers' },
            { title: worker?.name ?? 'Detail', href: '#' },
        ]}
    >
        {page}
    </AppLayout>
);
