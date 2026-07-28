import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { DataTable } from '@/components/data-table';
import type { Column } from '@/components/data-table';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import {
    store as createWorker,
    startAll,
    pauseAll,
    resumeAll,
    stopAll,
    start as startWorker,
    pause as pauseWorker,
    resume as resumeWorker,
    stop as stopWorker,
    destroy as deleteWorker,
} from '@/actions/App/Http/Controllers/WorkersController';

type Worker = {
    id: number;
    name: string;
    job_type: string | null;
    concurrency: number;
    created_at: string;
    updated_at: string;
};

const JOB_TYPE_LABELS: Record<string, string> = {
    transcode_media: 'Transcode Media',
    extract_subs: 'Extract Subtitles',
    convert_sub: 'Convert Subtitles',
};

export default function WorkersIndex({ workers }: { workers: Worker[] }) {
    const [addOpen, setAddOpen] = useState(false);
    const [newName, setNewName] = useState('');
    const [newJobType, setNewJobType] = useState('transcode_media');
    const [newConcurrency, setNewConcurrency] = useState(1);
    const [submitting, setSubmitting] = useState(false);

    const handleCreate = () => {
        if (!newName.trim() || !newJobType) return;

        setSubmitting(true);
        router.post(
            createWorker.url(),
            {
                name: newName,
                job_type: newJobType,
                concurrency: newConcurrency,
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    setSubmitting(false);
                    setAddOpen(false);
                    setNewName('');
                    setNewJobType('transcode_media');
                    setNewConcurrency(1);
                },
            },
        );
    };

    const handleDelete = (worker: Worker) => {
        if (!confirm(`Delete worker "${worker.name}"?`)) return;
        router.delete(deleteWorker.url({ worker: worker.id }), {
            preserveScroll: true,
        });
    };

    const confirmAction = (action: string, worker: Worker) =>
        confirm(`${action} worker "${worker.name}"?`);

    const columns: Column<Worker>[] = [
        {
            key: 'name',
            label: 'Name',
            render: (w) => (
                <a
                    href={`/workers/${w.id}`}
                    className="font-medium hover:underline"
                >
                    {w.name}
                </a>
            ),
        },
        {
            key: 'job_type',
            label: 'Job Type',
            render: (w) =>
                w.job_type ? JOB_TYPE_LABELS[w.job_type] ?? w.job_type : '-',
        },
        {
            key: 'concurrency',
            label: 'Concurrency',
            render: (w) => w.concurrency.toString(),
        },
        {
            key: 'updated_at',
            label: 'Last Heartbeat',
            render: (w) => new Date(w.updated_at).toLocaleString(),
        },
        {
            key: 'actions',
            label: '',
            render: (w) => (
                <div className="flex justify-end gap-1">
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            confirmAction('Start', w) &&
                            router.post(
                                startWorker.url({ worker: w.id }),
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        Start
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            confirmAction('Pause', w) &&
                            router.post(
                                pauseWorker.url({ worker: w.id }),
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        Pause
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            confirmAction('Resume', w) &&
                            router.post(
                                resumeWorker.url({ worker: w.id }),
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        Resume
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        onClick={() =>
                            confirmAction('Stop', w) &&
                            router.post(
                                stopWorker.url({ worker: w.id }),
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        Stop
                    </Button>
                    <Button
                        variant="destructive"
                        size="sm"
                        onClick={() => handleDelete(w)}
                    >
                        Delete
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Workers" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold">Workers</h1>
                    <Button onClick={() => setAddOpen(true)}>Add Worker</Button>
                </div>

                <div className="flex flex-wrap gap-2">
                    <Button
                        variant="secondary"
                        size="sm"
                        onClick={() =>
                            confirm('Start all workers?') &&
                            router.post(
                                startAll.url(),
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        Start All
                    </Button>
                    <Button
                        variant="secondary"
                        size="sm"
                        onClick={() =>
                            confirm('Pause all workers?') &&
                            router.post(
                                pauseAll.url(),
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        Pause All
                    </Button>
                    <Button
                        variant="secondary"
                        size="sm"
                        onClick={() =>
                            confirm('Resume all workers?') &&
                            router.post(
                                resumeAll.url(),
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        Resume All
                    </Button>
                    <Button
                        variant="secondary"
                        size="sm"
                        onClick={() =>
                            confirm('Stop all workers?') &&
                            router.post(
                                stopAll.url(),
                                {},
                                { preserveScroll: true },
                            )
                        }
                    >
                        Stop All
                    </Button>
                </div>

                <DataTable
                    columns={columns}
                    data={workers}
                    emptyMessage="No workers configured."
                />

                <Card>
                    <CardHeader>
                        <CardTitle>Worker Settings</CardTitle>
                        <CardDescription>
                            Global concurrency limits and pause control (moved
                            from Config → Workers)
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <p className="text-sm text-muted-foreground">
                            Per-worker concurrency is set in each worker's
                            detail view. Global pause settings are managed per
                            worker via the lifecycle controls above.
                        </p>
                    </CardContent>
                </Card>
            </div>

            <Dialog open={addOpen} onOpenChange={setAddOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Add Worker</DialogTitle>
                        <DialogDescription>
                            Create a new worker with a specific job type.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={newName}
                                onChange={(e) => setNewName(e.target.value)}
                                placeholder="My Worker"
                            />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="job_type">Job Type</Label>
                            <Select
                                value={newJobType}
                                onValueChange={setNewJobType}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="transcode_media">
                                        Transcode Media
                                    </SelectItem>
                                    <SelectItem value="extract_subs">
                                        Extract Subtitles
                                    </SelectItem>
                                    <SelectItem value="convert_sub">
                                        Convert Subtitles
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="concurrency">Concurrency</Label>
                            <Input
                                id="concurrency"
                                type="number"
                                min={1}
                                max={99}
                                value={newConcurrency}
                                onChange={(e) =>
                                    setNewConcurrency(Number(e.target.value))
                                }
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setAddOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button
                            onClick={handleCreate}
                            disabled={submitting || !newName.trim()}
                        >
                            Create
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
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
