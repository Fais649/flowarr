import { Head, router } from '@inertiajs/react';
import { update } from '@/actions/App/Http/Controllers/WorkersController';
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

export default function WorkersIndex({ workers }: { workers: Worker[] }) {
    const handleUpdate = (worker: Worker, data: Record<string, unknown>) => {
        router.patch(
            update.url({ worker: worker.id }),
            data,
            { preserveScroll: true },
        );
    };

    return (
        <>
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
                                    <Switch
                                        checked={worker.enabled}
                                        onCheckedChange={(checked) =>
                                            handleUpdate(worker, { enabled: checked })
                                        }
                                    />
                                </div>
                                <CardDescription>
                                    {worker.enabled ? 'Active' : 'Disabled'}
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label className="text-xs text-muted-foreground">
                                        Concurrency
                                    </Label>
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
                                    <Label
                                        htmlFor={`replace-${worker.id}`}
                                        className="text-xs text-muted-foreground cursor-pointer"
                                    >
                                        Replace Original
                                    </Label>
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
        </>
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
