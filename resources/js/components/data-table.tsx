import { ArrowUpDown } from 'lucide-react';
import type { ReactNode } from 'react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { cn } from '@/lib/utils';

export type Column<T> = {
    key: string;
    label: string;
    sortable?: boolean;
    render: (item: T) => ReactNode;
    className?: string;
};

type DataTableProps<T> = {
    columns: Column<T>[];
    data: T[];
    sort?: string;
    onSort?: (key: string) => void;
    emptyMessage?: string;
};

export function DataTable<T extends { id: number | string }>({
    columns,
    data,
    sort,
    onSort,
    emptyMessage = 'No records found.',
}: DataTableProps<T>) {
    if (data.length === 0) {
        return (
            <p className="py-8 text-center text-sm text-muted-foreground">
                {emptyMessage}
            </p>
        );
    }

    return (
        <Table>
            <TableHeader>
                <TableRow>
                    {columns.map((col) => (
                        <TableHead
                            key={col.key}
                            className={cn(
                                col.sortable && 'cursor-pointer select-none',
                                col.className,
                            )}
                            onClick={() => col.sortable && onSort?.(col.key)}
                        >
                            <span className="inline-flex items-center gap-1">
                                {col.label}
                                {col.sortable && sort === col.key && (
                                    <ArrowUpDown className="size-3" />
                                )}
                            </span>
                        </TableHead>
                    ))}
                </TableRow>
            </TableHeader>
            <TableBody>
                {data.map((item) => (
                    <TableRow key={item.id}>
                        {columns.map((col) => (
                            <TableCell key={col.key} className={col.className}>
                                {col.render(item)}
                            </TableCell>
                        ))}
                    </TableRow>
                ))}
            </TableBody>
        </Table>
    );
}
