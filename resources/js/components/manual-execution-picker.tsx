import { useForm } from '@inertiajs/react';
import { Folder, Loader2 } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import {
    files,
    store,
} from '@/actions/App/Http/Controllers/ManualExecutionsController';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { JobTypeLabels } from '@/types/models';

export type PickerLibrary = {
    id: number;
    base_path: string;
    workers?: { job_type: string | null; enabled: boolean }[];
};
type Entry = { name: string; path: string; directory: boolean };

export default function ManualExecutionPicker({
    libraries,
}: {
    libraries: PickerLibrary[];
}) {
    const [open, setOpen] = useState(false);
    const [path, setPath] = useState('');
    const [parent, setParent] = useState<string | null>(null);
    const [entries, setEntries] = useState<Entry[]>([]);
    const [loading, setLoading] = useState(false);
    const [loadError, setLoadError] = useState<string | null>(null);
    const [reload, setReload] = useState(0);
    const form = useForm({
        library_id: libraries[0]?.id ?? 0,
        job_id: 'transcode_media',
        files: [] as string[],
        mode: 'enqueue',
    });
    const library = libraries.find((item) => item.id === form.data.library_id);
    const operations =
        library?.workers?.filter(
            (worker) => worker.enabled && worker.job_type,
        ) ?? [];

    const loadFolder = useCallback(
        (signal: AbortSignal) => {
            void fetch(
                files(form.data.library_id, { query: path ? { path } : {} })
                    .url,
                { headers: { Accept: 'application/json' }, signal: signal },
            )
                .then(async (response) => {
                    if (!response.ok) {
                        throw new Error(
                            'Could not read this folder. Check library permissions.',
                        );
                    }

                    const data = await response.json();

                    if (!signal.aborted) {
                        setEntries(data.entries);
                        setParent(data.parent);
                    }
                })
                .catch((error: Error) => {
                    if (!signal.aborted) {
                        setLoadError(error.message);
                    }
                })
                .finally(() => {
                    if (!signal.aborted) {
                        setLoading(false);
                    }
                });
        },
        [form.data.library_id, path],
    );

    useEffect(() => {
        if (!open || !form.data.library_id) {
            return;
        }

        const abort = new AbortController();
        loadFolder(abort.signal);

        return () => abort.abort();
    }, [open, form.data.library_id, loadFolder, reload]);

    const navigate = (nextPath: string) => {
        setLoading(true);
        setLoadError(null);
        setPath(nextPath);
    };

    const submit = (mode: string) => {
        form.transform((data) => ({ ...data, mode }));
        form.post(store().url, {
            onSuccess: () => {
                setOpen(false);
                form.setData('files', []);
            },
        });
    };

    return (
        <>
            <Button
                variant="outline"
                onClick={() => {
                    setLoading(true);
                    setLoadError(null);
                    setOpen(true);
                }}
                disabled={libraries.length === 0}
            >
                Select files to execute
            </Button>
            <Dialog
                open={open}
                onOpenChange={(value) => {
                    if (value) {
                        setLoading(true);
                        setLoadError(null);
                    }

                    setOpen(value);
                }}
            >
                <DialogContent className="sm:max-w-2xl">
                    <DialogHeader>
                        <DialogTitle>Select files to execute</DialogTitle>
                        <DialogDescription>
                            Choose files from a library and an operation. Run
                            now takes the next available worker slot ahead of
                            regular queued work. Playback pauses and processing
                            schedules still apply.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid grid-cols-2 gap-3">
                        <div className="space-y-2">
                            <Label htmlFor="manual_library">Library</Label>
                            <select
                                id="manual_library"
                                className="w-full rounded-md border bg-background p-2 text-sm"
                                value={form.data.library_id}
                                onChange={(event) => {
                                    form.setData({
                                        ...form.data,
                                        library_id: Number(event.target.value),
                                        files: [],
                                    });
                                    setLoading(true);
                                    setLoadError(null);
                                    setPath('');
                                }}
                            >
                                {libraries.map((item) => (
                                    <option key={item.id} value={item.id}>
                                        {item.base_path}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="manual_operation">Operation</Label>
                            <select
                                id="manual_operation"
                                className="w-full rounded-md border bg-background p-2 text-sm"
                                value={form.data.job_id}
                                onChange={(event) =>
                                    form.setData('job_id', event.target.value)
                                }
                            >
                                <option value="">Select operation</option>
                                {operations.map((worker) => (
                                    <option
                                        key={worker.job_type}
                                        value={worker.job_type!}
                                    >
                                        {JobTypeLabels[worker.job_type!]}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <div className="flex items-center gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            disabled={!parent || loading}
                            onClick={() => parent && navigate(parent)}
                        >
                            Up
                        </Button>
                        <code className="truncate text-xs">
                            {path || library?.base_path}
                        </code>
                    </div>
                    <div className="max-h-72 min-h-40 overflow-auto rounded-md border p-2">
                        {loading ? (
                            <Loader2
                                className="m-auto animate-spin"
                                aria-label="Loading files"
                            />
                        ) : loadError ? (
                            <div role="alert" className="space-y-2">
                                <p>{loadError}</p>
                                <Button
                                    variant="outline"
                                    onClick={() => {
                                        setLoading(true);
                                        setLoadError(null);
                                        setReload((value) => value + 1);
                                    }}
                                >
                                    Retry
                                </Button>
                            </div>
                        ) : entries.length === 0 ? (
                            <p className="p-3 text-sm text-muted-foreground">
                                No media files or folders found.
                            </p>
                        ) : (
                            entries.map((entry) =>
                                entry.directory ? (
                                    <button
                                        type="button"
                                        key={entry.path}
                                        className="flex w-full items-center gap-2 rounded p-2 text-left text-sm hover:bg-accent"
                                        onClick={() => navigate(entry.path)}
                                    >
                                        <Folder className="size-4" />
                                        {entry.name}
                                    </button>
                                ) : (
                                    <label
                                        key={entry.path}
                                        className="flex items-center gap-2 rounded p-2 text-sm hover:bg-accent"
                                    >
                                        <Checkbox
                                            checked={form.data.files.includes(
                                                entry.path,
                                            )}
                                            onCheckedChange={(checked) =>
                                                form.setData(
                                                    'files',
                                                    checked
                                                        ? [
                                                              ...form.data
                                                                  .files,
                                                              entry.path,
                                                          ]
                                                        : form.data.files.filter(
                                                              (file) =>
                                                                  file !==
                                                                  entry.path,
                                                          ),
                                                )
                                            }
                                        />
                                        {entry.name}
                                    </label>
                                ),
                            )
                        )}
                    </div>
                    <p className="text-sm text-muted-foreground">
                        {form.data.files.length} file(s) selected across
                        folders. Files already active or awaiting replacement
                        are skipped.
                    </p>
                    {Object.entries(form.errors).map(([key, error]) => (
                        <p
                            key={key}
                            role="alert"
                            className="text-sm text-destructive"
                        >
                            {error}
                        </p>
                    ))}
                    <div className="flex justify-end gap-2">
                        <Button
                            variant="outline"
                            disabled={
                                form.processing ||
                                form.data.files.length === 0 ||
                                !form.data.job_id
                            }
                            onClick={() => submit('enqueue')}
                        >
                            Enqueue
                        </Button>
                        <Button
                            disabled={
                                form.processing ||
                                form.data.files.length === 0 ||
                                !form.data.job_id
                            }
                            onClick={() => submit('now')}
                        >
                            Run now
                        </Button>
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}
