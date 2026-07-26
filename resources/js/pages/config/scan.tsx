import { Head, useForm } from '@inertiajs/react';
import type { FormEventHandler } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit as editScan, update as updateScan } from '@/routes/config/scan';

type Props = {
    concurrency: number;
};

export default function Scan({ concurrency }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        concurrency,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(updateScan.url(), {
            preserveScroll: true,
        });
    };

    return (
        <>
            <Head title="Scan settings" />

            <h1 className="sr-only">Scan settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Scan settings"
                    description="Configure how many libraries scan in parallel"
                />

                <form onSubmit={submit} className="space-y-6">
                    <div className="grid max-w-xs gap-2">
                        <Label htmlFor="concurrency">
                            Concurrent library scans
                        </Label>
                        <p className="text-sm text-muted-foreground">
                            Maximum number of libraries scanned at the same
                            time. Higher values speed up scanning but use more
                            resources.
                        </p>
                        <Input
                            id="concurrency"
                            type="number"
                            min={1}
                            className="mt-1 block w-full"
                            value={data.concurrency}
                            onChange={(e) =>
                                setData('concurrency', Number(e.target.value))
                            }
                        />
                        <InputError
                            className="mt-2"
                            message={errors.concurrency}
                        />
                    </div>

                    <div className="flex items-center gap-4">
                        <Button
                            disabled={processing}
                            data-test="update-scan-settings-button"
                        >
                            Save
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

Scan.layout = {
    breadcrumbs: [
        {
            title: 'Scan settings',
            href: editScan(),
        },
    ],
};
