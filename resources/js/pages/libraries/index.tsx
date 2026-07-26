import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import { DataTable } from '@/components/data-table';
import type { Column } from '@/components/data-table';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Library = {
    id: number;
    base_path: string;
    status: string;
    scan_interval: number;
    last_scan: string | null;
    library_jobs: { id: number; job_id: string }[];
};

export default function LibrariesIndex({
    libraries,
}: {
    libraries: Library[];
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Libraries', href: '/libraries' },
    ];

    const columns: Column<Library>[] = [
        {
            key: 'base_path',
            label: 'Path',
            render: (lib) => (
                <Link
                    href={`/libraries/${lib.id}`}
                    className="font-medium hover:underline"
                >
                    {lib.base_path}
                </Link>
            ),
        },
        {
            key: 'status',
            label: 'Status',
            render: (lib) => <StatusBadge status={lib.status} />,
        },
        {
            key: 'scan_interval',
            label: 'Scan Interval',
            render: (lib) => `${lib.scan_interval}s`,
        },
        {
            key: 'last_scan',
            label: 'Last Scan',
            render: (lib) => lib.last_scan ?? 'Never',
        },
        {
            key: 'jobs',
            label: 'Enabled Jobs',
            render: (lib) => lib.library_jobs.length.toString(),
        },
        {
            key: 'actions',
            label: '',
            render: (lib) => (
                <div className="flex justify-end gap-2">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={`/libraries/${lib.id}`}>View</Link>
                    </Button>
                </div>
            ),
        },
    ];

    return (
        <>
            <Head title="Libraries" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold">Libraries</h1>
                    <Button asChild>
                        <Link href="/libraries/create">
                            <Plus className="mr-1 size-4" />
                            Add Library
                        </Link>
                    </Button>
                </div>
                <DataTable
                    columns={columns}
                    data={libraries}
                    emptyMessage="No libraries configured yet."
                />
            </div>
        </>
    );
}

LibrariesIndex.layout = (page: React.ReactNode) => (
    <AppLayout
        breadcrumbs={[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Libraries', href: '/libraries' },
        ]}
    >
        {page}
    </AppLayout>
);
