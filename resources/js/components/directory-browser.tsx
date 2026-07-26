import { ChevronRightIcon, FolderIcon, Loader2Icon } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
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
    children: TreeNode[];
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
    children: TreeNode[];
    depth: number;
    selectedPath: string | null;
    onSelectPath: (path: string) => void;
}) {
    const [expanded, setExpanded] = useState(depth === 0);
    const isSelected = selectedPath === path;

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
                {children.length > 0 && (
                    <span
                        role="button"
                        tabIndex={-1}
                        onClick={(e) => {
                            e.stopPropagation();
                            setExpanded((prev) => !prev);
                        }}
                        onKeyDown={(e) =>
                            e.key === 'Enter' && setExpanded((prev) => !prev)
                        }
                        className="flex size-4 shrink-0 cursor-pointer items-center justify-center rounded hover:bg-muted"
                    >
                        <ChevronRightIcon
                            className={`size-3.5 transition-transform ${expanded ? 'rotate-90' : ''}`}
                        />
                    </span>
                )}
                {children.length === 0 && <span className="size-4 shrink-0" />}
                <FolderIcon className="size-4 shrink-0 text-blue-500" />
                <span className="truncate">{name}</span>
            </div>

            {expanded && children.length > 0 && (
                <div>
                    {children.map((child) => (
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
    const [selectedPath, setSelectedPath] = useState<string | null>(null);

    const fetchTree = useCallback(async () => {
        setLoading(true);
        setTree(null);

        try {
            const token =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') ?? '';
            const response = await fetch(
                '/libraries/directories?path=/&depth=5',
                {
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                },
            );

            if (!response.ok) {
                throw new Error('Failed to fetch');
            }

            const data = await response.json();
            setTree(data.directories);
        } catch {
            setTree([]);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        if (open) {
            setSelectedPath(null);
            fetchTree();
        }
    }, [open, fetchTree]);

    const handleSelect = () => {
        if (selectedPath) {
            onSelect(selectedPath);
            onOpenChange(false);
        }
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
                    {!loading && tree && tree.length === 0 && (
                        <div className="flex min-h-48 items-center justify-center">
                            <p className="text-sm text-muted-foreground">
                                No directories found.
                            </p>
                        </div>
                    )}
                    {!loading && tree && tree.length > 0 && (
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
