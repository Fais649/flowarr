import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, RotateCcw, XCircle } from 'lucide-react';
import { StatusBadge } from '@/components/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';

type Execution = {
    id: number;
    file_path: string;
    status: string;
    library_job: {
        id: number;
        job_id: string;
        library: { id: number; base_path: string };
    };
    created_at: string;
    finished_at: string | null;
};

export default function ExecutionDetail({
    execution,
}: {
    execution: Execution;
}) {
    const handleRetry = () => {
        if (!confirm('Retry this execution?')) {
return;
}

        router.post(`/executions/${execution.id}/retry`);
    };

    const handleCancel = () => {
        if (!confirm('Cancel this execution?')) {
return;
}

        router.post(`/executions/${execution.id}/cancel`);
    };


    return (
        <>
            <Head title={`Execution #${execution.id}`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/executions">
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <div className="flex-1">
                        <h1 className="text-2xl font-bold">
                            Execution #{execution.id}
                        </h1>
                        <StatusBadge status={execution.status} />
                    </div>
                    <div className="flex gap-2">
                        {execution.status === 'failed' && (
                            <Button variant="outline" onClick={handleRetry}>
                                <RotateCcw className="mr-1 size-4" />
                                Retry
                            </Button>
                        )}
                        {(execution.status === 'queued' ||
                            execution.status === 'processing') && (
                            <Button variant="outline" onClick={handleCancel}>
                                <XCircle className="mr-1 size-4" />
                                Cancel
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div>
                                <span className="text-sm text-muted-foreground">
                                    File
                                </span>
                                <p className="font-medium break-all">
                                    {execution.file_path}
                                </p>
                            </div>
                            <div>
                                <span className="text-sm text-muted-foreground">
                                    Library
                                </span>
                                <p className="font-medium">
                                    {execution.library_job?.library
                                        ?.base_path ?? '-'}
                                </p>
                            </div>
                            <div>
                                <span className="text-sm text-muted-foreground">
                                    Job Type
                                </span>
                                <p className="font-medium">
                                    {execution.library_job?.job_id ?? '-'}
                                </p>
                            </div>
                            <div>
                                <span className="text-sm text-muted-foreground">
                                    Created
                                </span>
                                <p className="font-medium">
                                    {new Date(
                                        execution.created_at,
                                    ).toLocaleString()}
                                </p>
                            </div>
                            {execution.finished_at && (
                                <div>
                                    <span className="text-sm text-muted-foreground">
                                        Finished
                                    </span>
                                    <p className="font-medium">
                                        {new Date(
                                            execution.finished_at,
                                        ).toLocaleString()}
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}

ExecutionDetail.layout = (page: React.ReactNode) => (
    <AppLayout
        breadcrumbs={[
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Executions', href: '/executions' },
            { title: 'Detail', href: '#' },
        ]}
    >
        {page}
    </AppLayout>
);
