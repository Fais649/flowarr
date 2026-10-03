import {
    AlertCircle,
    ChevronRightIcon,
    FolderIcon,
    Loader2Icon,
} from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type TreeNode = {
    name: string;
    path: string;
    children?: TreeNode[];
};

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    onSelect: (path: string) => void;
};

function DirectoryNode({
    name,
    path,
    children,
    depth,
    selectedPath,
    onSelectPath,
}: {
    name: string;
    path: string;
    children?: TreeNode[];
    depth: number;
    selectedPath: string | null;
    onSelectPath: (path: string) => void;
}) {
    const [expanded, setExpanded] = useState(depth === 0);
    const [nodes, setNodes] = useState<TreeNode[] | undefined>(children);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const isSelected = selectedPath === path;

    const toggle = async () => {
        if (loading) {
            return;
        }

        if (nodes !== undefined) {
            setExpanded((value) => !value);

            return;
        }

        setLoading(true);
        setError(null);

        try {
            const response = await fetch(
                `/libraries/directories?path=${encodeURIComponent(path)}&depth=0`,
                {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                },
            );

            if (!response.ok) {
                throw new Error(
                    'Could not load this folder. Expand it to retry.',
                );
            }

            const data = await response.json();
            setNodes(data.directories);
            setExpanded(true);
        } catch (error) {
            setError(
                error instanceof Error
                    ? error.message
                    : 'Could not load this folder.',
            );
        } finally {
            setLoading(false);
        }
    };

    return (
        <div>
            <div
                role="button"
                tabIndex={0}
                onClick={() => onSelectPath(path)}
                onKeyDown={(e) => e.key === 'Enter' && onSelectPath(path)}
                className={`flex w-full cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-left text-sm transition-colors hover:bg-accent ${
                    isSelected
                        ? 'bg-accent font-medium text-accent-foreground'
                        : ''
                }`}
                style={{ paddingLeft: `${depth * 16 + 8}px` }}
            >
                {nodes === undefined || nodes.length > 0 ? (
                    <button
                        type="button"
                        aria-label={`${expanded ? 'Collapse' : 'Expand'} ${name}`}
                        aria-expanded={expanded}
                        disabled={loading}
                        onClick={(e) => {
                            e.stopPropagation();
                            void toggle();
                        }}
                        className="flex size-4 shrink-0 items-center justify-center rounded hover:bg-muted"
                    >
                        {loading ? (
                            <Loader2Icon className="size-3.5 animate-spin" />
                        ) : (
                            <ChevronRightIcon
                                className={`size-3.5 transition-transform ${expanded ? 'rotate-90' : ''}`}
                            />
                        )}
                    </button>
                ) : (
                    <span className="size-4 shrink-0" />
                )}
                <FolderIcon className="size-4 shrink-0 text-blue-500" />
                <span className="truncate">{name}</span>
            </div>

            {error && (
                <p role="alert" className="px-6 text-sm text-destructive">
                    {error}
                </p>
            )}
            {expanded && nodes && nodes.length > 0 && (
                <div>
                    {nodes.map((child) => (
                        <DirectoryNode
                            key={child.path}
                            name={child.name}
                            path={child.path}
                            children={child.children}
                            depth={depth + 1}
                            selectedPath={selectedPath}
                            onSelectPath={onSelectPath}
                        />
                    ))}
                </div>
            )}
        </div>
    );
}

export default function DirectoryBrowser({
    open,
    onOpenChange,
    onSelect,
}: Props) {
    const [tree, setTree] = useState<TreeNode[] | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [selectedPath, setSelectedPath] = useState<string | null>(null);
    const abortRef = useRef<AbortController | null>(null);

    const fetchTree = useCallback(async (signal?: AbortSignal) => {
        setLoading(true);
        setTree(null);
        setError(null);
        setSelectedPath(null);

        try {
            const token =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') ?? '';
            const response = await fetch(
                '/libraries/directories?path=/&depth=0',
                {
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    signal,
                },
            );

            if (!response.ok) {
                if (response.status === 401) {
                    throw new Error(
                        'Session expired. Please refresh the page and try again.',
                    );
                }

                if (response.status === 404) {
                    throw new Error(
                        'Directory API not found. Try refreshing the page.',
                    );
                }

                throw new Error(
                    `Server error (${response.status}). Check server logs.`,
                );
            }

            const data = await response.json();
            setTree(data.directories);
        } catch (e) {
            if (e instanceof Error && e.name === 'AbortError') {
                return;
            }

            setError(
                e instanceof Error
                    ? e.message
                    : 'Could not load directories. Check the server status.',
            );
            setTree([]);
        } finally {
            if (!signal?.aborted) {
                setLoading(false);
            }
        }
    }, []);

    useEffect(() => {
        if (!open) {
            return;
        }

        const controller = new AbortController();
        abortRef.current = controller;

        void (async (signal: AbortSignal) => {
            await fetchTree(signal);
        })(controller.signal);

        return () => abortRef.current?.abort();
    }, [open, fetchTree]);

    const handleSelect = () => {
        if (selectedPath) {
            onSelect(selectedPath);
            onOpenChange(false);
        }
    };

    const handleRetry = () => {
        abortRef.current?.abort();

        const controller = new AbortController();
        abortRef.current = controller;

        void fetchTree(controller.signal);
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(val) => {
                if (!val) {
                    setSelectedPath(null);
                }

                onOpenChange(val);
            }}
        >
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Browse Directories</DialogTitle>
                    <DialogDescription>
                        Select a directory to use as the library base path.
                    </DialogDescription>
                </DialogHeader>

                <div className="max-h-80 min-h-48 overflow-y-auto rounded-md border p-2">
                    {loading && (
                        <div className="flex min-h-48 items-center justify-center">
                            <Loader2Icon className="size-6 animate-spin text-muted-foreground" />
                        </div>
                    )}
                    {!loading && error && (
                        <div className="flex min-h-48 flex-col items-center justify-center gap-3 px-4">
                            <AlertCircle className="size-6 text-destructive" />
                            <p className="text-center text-sm text-destructive">
                                {error}
                            </p>
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={handleRetry}
                            >
                                Retry
                            </Button>
                        </div>
                    )}
                    {!loading && !error && tree && tree.length === 0 && (
                        <div className="flex min-h-48 items-center justify-center">
                            <p className="text-sm text-muted-foreground">
                                No directories found.
                            </p>
                        </div>
                    )}
                    {!loading && !error && tree && tree.length > 0 && (
                        <div>
                            <DirectoryNode
                                name="/ (root)"
                                path="/"
                                children={tree}
                                depth={0}
                                selectedPath={selectedPath}
                                onSelectPath={setSelectedPath}
                            />
                        </div>
                    )}
                </div>

                {selectedPath && (
                    <p className="rounded-md bg-muted px-3 py-2 text-sm text-muted-foreground">
                        Selected:{' '}
                        <code className="font-medium text-foreground">
                            {selectedPath}
                        </code>
                    </p>
                )}

                <div className="flex justify-end gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => {
                            onOpenChange(false);
                            setSelectedPath(null);
                        }}
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        onClick={handleSelect}
                        disabled={!selectedPath}
                    >
                        Select Directory
                    </Button>
                </div>
            </DialogContent>
        </Dialog>
    );
}
