import { Head, useForm } from '@inertiajs/react';
import { useState } from 'react';
import DirectoryBrowser from '@/components/directory-browser';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';

export default function CreateLibrary({
    library,
}: {
    library?: { id: number; base_path: string; scan_interval: number };
}) {
    const [browserOpen, setBrowserOpen] = useState(false);
    const { data, setData, post, patch, processing, errors } = useForm({
        base_path: library?.base_path ?? '',
        scan_interval: library?.scan_interval ?? 43200,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (library) {
            patch(`/libraries/${library.id}`);
        } else {
            post('/libraries');
        }
    };


    return (
        <>
            <Head title={library ? 'Edit Library' : 'Create Library'} />
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
                            <Button type="submit" disabled={processing}>
                                {library ? 'Update Library' : 'Create Library'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>

            <DirectoryBrowser
                open={browserOpen}
                onOpenChange={setBrowserOpen}
                onSelect={(path) => setData('base_path', path)}
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
