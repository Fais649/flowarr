import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import DirectoryBrowser from '@/components/directory-browser';
import OriginalHandlingFields from '@/components/original-handling-fields';
import type { OriginalHandling } from '@/components/original-handling-fields';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { Worker } from '@/types/models';
import { JobTypeLabels } from '@/types/models';

function LibraryForm({
    library,
    workers,
}: {
    library?: {
        id: number;
        base_path: string;
        scan_interval: number;
    } & Partial<OriginalHandling>;
    workers: Worker[];
}) {
    const [browserOpen, setBrowserOpen] = useState(false);
    const { data, setData, post, patch, processing, errors } = useForm({
        base_path: library?.base_path ?? '',
        scan_interval: library?.scan_interval ?? 43200,
        original_handling: library
            ? (library.original_handling ?? null)
            : 'keep',
        replacement_start: library?.replacement_start ?? '',
        replacement_end: library?.replacement_end ?? '',
        worker_ids: workers.map((worker) => worker.id),
    });

    const toggleWorker = (id: number, checked: boolean) => {
        setData(
            'worker_ids',
            checked
                ? [...data.worker_ids, id]
                : data.worker_ids.filter((workerId) => workerId !== id),
        );
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (library) {
            patch(`/libraries/${library.id}`);
        } else {
            post('/libraries');
        }
    };

    return (
        <div className="flex h-full flex-1 flex-col gap-4 p-4">
            <h1 className="text-2xl font-bold">
                {library ? 'Edit Library' : 'Create Library'}
            </h1>
            <Card className="max-w-lg">
                <CardHeader>
                    <CardTitle>Library Details</CardTitle>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="base_path">Base Path</Label>
                            <div className="flex gap-2">
                                <Input
                                    id="base_path"
                                    value={data.base_path}
                                    onChange={(e) =>
                                        setData('base_path', e.target.value)
                                    }
                                    placeholder="/path/to/media"
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setBrowserOpen(true)}
                                >
                                    Browse
                                </Button>
                            </div>
                            {errors.base_path && (
                                <p className="text-sm text-destructive">
                                    {errors.base_path}
                                </p>
                            )}
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="scan_interval">
                                Scan Interval (seconds)
                            </Label>
                            <Input
                                id="scan_interval"
                                type="number"
                                value={data.scan_interval}
                                onChange={(e) =>
                                    setData(
                                        'scan_interval',
                                        Number(e.target.value),
                                    )
                                }
                                min={60}
                            />
                            {errors.scan_interval && (
                                <p className="text-sm text-destructive">
                                    {errors.scan_interval}
                                </p>
                            )}
                        </div>
                        {!library && workers.length > 0 && (
                            <div className="space-y-2">
                                <Label>Jobs to run</Label>
                                {workers.map((worker) => (
                                    <label
                                        key={worker.id}
                                        className="flex items-center gap-2 text-sm"
                                    >
                                        <Checkbox
                                            checked={data.worker_ids.includes(
                                                worker.id,
                                            )}
                                            onCheckedChange={(checked) =>
                                                toggleWorker(
                                                    worker.id,
                                                    checked === true,
                                                )
                                            }
                                        />
                                        {worker.job_type
                                            ? (JobTypeLabels[worker.job_type] ??
                                              worker.name)
                                            : worker.name}
                                    </label>
                                ))}
                                <p className="text-xs text-muted-foreground">
                                    The library is scanned right after it is
                                    created. Jobs can be changed later on the
                                    library page.
                                </p>
                            </div>
                        )}
                        <OriginalHandlingFields
                            value={data}
                            onChange={(value) => setData({ ...data, ...value })}
                            errors={{
                                original_handling: errors.original_handling,
                                replacement_start: errors.replacement_start,
                                replacement_end: errors.replacement_end,
                            }}
                        />
                        <Button type="submit" disabled={processing}>
                            {library ? 'Update Library' : 'Create Library'}
                        </Button>
                    </form>
                </CardContent>
            </Card>

            <DirectoryBrowser
                open={browserOpen}
                onOpenChange={setBrowserOpen}
                onSelect={(path) => setData('base_path', path)}
            />
        </div>
    );
}

export default function CreateLibrary({
    library,
    workers = [],
}: {
    library?: {
        id: number;
        base_path: string;
        scan_interval: number;
    } & Partial<OriginalHandling>;
    workers?: Worker[];
}) {
    return (
        <>
            <Head title={library ? 'Edit Library' : 'Create Library'} />
            <LibraryForm
                key={library?.id ?? 'new'}
                library={library}
                workers={workers}
            />
        </>
    );
}

CreateLibrary.layout = (page: React.ReactNode) => (
    <AppLayout
        breadcrumbs={[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Libraries', href: '/libraries' },
            { title: 'Create', href: '#' },
        ]}
    >
        {page}
    </AppLayout>
);
