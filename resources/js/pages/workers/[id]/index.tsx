import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Worker = {
    id: number;
    name: string;
    created_at: string;
    updated_at: string;
};

export default function WorkerDetail({ worker }: { worker: Worker }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Workers', href: '/workers' },
        { title: worker.name, href: '#' },
    ];

    return (
        <>
            <Head title={worker.name} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/workers">
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-bold">{worker.name}</h1>
                </div>

                <Card className="max-w-lg">
                    <CardHeader>
                        <CardTitle>Worker Info</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <div>
                            <span className="text-sm text-muted-foreground">
                                Name
                            </span>
                            <p className="font-medium">{worker.name}</p>
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
                                Last Heartbeat
                            </span>
                            <p className="font-medium">
                                {new Date(worker.updated_at).toLocaleString()}
                            </p>
                        </div>
                    </CardContent>
                </Card>
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
