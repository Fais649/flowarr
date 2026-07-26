import { Head, Link } from '@inertiajs/react';
import { DataTable } from '@/components/data-table';
import type { Column } from '@/components/data-table';
import AppLayout from '@/layouts/app-layout';

type Worker = {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
};

export default function WorkersIndex({ workers }: { workers: Worker[] }) {
    const columns: Column<Worker>[] = [
        {
            key: 'name',
            label: 'Name',
            render: (w) => (
                <Link
                    href={`/workers/${w.id}`}
                    className="font-medium hover:underline"
                >
                    {w.name}
                </Link>
            ),
        },
        {
            key: 'created_at',
            label: 'Registered',
            render: (w) => new Date(w.created_at).toLocaleString(),
        },
        {
            key: 'updated_at',
            label: 'Last Heartbeat',
            render: (w) => new Date(w.updated_at).toLocaleString(),
        },
    ];


    return (
        <>
            <Head title="Workers" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <h1 className="text-2xl font-bold">Workers</h1>
                <DataTable
                    columns={columns}
                    data={workers}
                    emptyMessage="No workers registered."
                />
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
