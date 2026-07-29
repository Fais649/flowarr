import { Head, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
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

export default function WorkerDetail({ worker }: { worker: Worker }) {
    const handleUpdate = (data: Record<string, unknown>) => {
        router.patch(
            update.url({ worker: worker.id }),
            data,
            { preserveScroll: true },
        );
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
                                <div>
                                    <Label>Enabled</Label>
                                    <p className="text-xs text-muted-foreground">
                                        {worker.enabled ? 'Worker is active' : 'Worker is disabled'}
                                    </p>
                                </div>
                                <Switch
                                    checked={worker.enabled}
                                    onCheckedChange={(checked) =>
                                        handleUpdate({ enabled: checked })
                                    }
                                />
                            </div>

                            <div className="space-y-2">
                                <Label>Concurrency</Label>
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
                                <div>
                                    <Label>Replace Original</Label>
                                    <p className="text-xs text-muted-foreground">
                                        Replace original files with processed versions
                                    </p>
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
        </>
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
