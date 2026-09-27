import { Head, router, useForm, usePage } from '@inertiajs/react';
import { Copy, KeyRound, Send } from 'lucide-react';
import type { FormEventHandler } from 'react';
import {
    generateApiToken,
    revokeApiToken,
    testNotification,
} from '@/actions/App/Http/Controllers/Config/ProcessingSettingsController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { ProcessingBanner } from '@/components/processing-banner';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Switch } from '@/components/ui/switch';
import { edit, update } from '@/routes/config/processing';
import type { ProcessingState } from '@/types/models';

type Settings = {
    window_enabled: boolean;
    window_start: string;
    window_end: string;
    stream_timeout_minutes: number;
    webhook_url: string;
    notify_on_completed: boolean;
    notify_on_failed: boolean;
};

type Props = {
    settings: Settings;
    processing: ProcessingState;
    timezone: string;
    hasApiToken: boolean;
    webhookUrls: Record<'jellyfin' | 'plex' | 'emby', string>;
    webhookTokenConfigured: boolean;
};

function SwitchField({
    id,
    label,
    description,
    checked,
    onChange,
}: {
    id: string;
    label: string;
    description?: string;
    checked: boolean;
    onChange: (checked: boolean) => void;
}) {
    return (
        <div className="flex items-center justify-between gap-4">
            <div>
                <Label htmlFor={id}>{label}</Label>
                {description && (
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            <Switch id={id} checked={checked} onCheckedChange={onChange} />
        </div>
    );
}

function copy(text: string) {
    void navigator.clipboard?.writeText(text);
}

function ProcessingForm({
    settings,
    timezone,
}: {
    settings: Settings;
    timezone: string;
}) {
    const { data, setData, post, processing, errors, isDirty } =
        useForm<Settings>(settings);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(update.url(), { preserveScroll: true });
    };

    return (
        <form onSubmit={submit} className="space-y-10">
            <section className="space-y-6">
                <Heading
                    variant="small"
                    title="Processing window"
                    description="Only run media jobs during these hours, e.g. overnight. Running jobs are suspended outside the window and resume when it opens again."
                />

                <SwitchField
                    id="window_enabled"
                    label="Restrict processing to a daily window"
                    checked={data.window_enabled}
                    onChange={(checked) => setData('window_enabled', checked)}
                />

                {data.window_enabled && (
                    <div className="grid max-w-md grid-cols-2 gap-4">
                        <div className="grid gap-2">
                            <Label htmlFor="window_start">Start</Label>
                            <Input
                                id="window_start"
                                type="time"
                                value={data.window_start}
                                onChange={(e) =>
                                    setData('window_start', e.target.value)
                                }
                            />
                            <InputError message={errors.window_start} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="window_end">End</Label>
                            <Input
                                id="window_end"
                                type="time"
                                value={data.window_end}
                                onChange={(e) =>
                                    setData('window_end', e.target.value)
                                }
                            />
                            <InputError message={errors.window_end} />
                        </div>
                        <p className="col-span-2 text-xs text-muted-foreground">
                            Times are in the server timezone ({timezone}). A
                            window may wrap around midnight, e.g. 22:00–06:00.
                        </p>
                    </div>
                )}
            </section>

            <section className="space-y-6">
                <Heading
                    variant="small"
                    title="Media server playback"
                    description="Processing pauses while Jellyfin, Plex or Emby report active playback."
                />

                <div className="grid max-w-xs gap-2">
                    <Label htmlFor="stream_timeout_minutes">
                        Forget streams after (minutes)
                    </Label>
                    <Input
                        id="stream_timeout_minutes"
                        type="number"
                        min={5}
                        max={1440}
                        value={data.stream_timeout_minutes}
                        onChange={(e) =>
                            setData(
                                'stream_timeout_minutes',
                                Number(e.target.value),
                            )
                        }
                    />
                    <p className="text-xs text-muted-foreground">
                        Safety net for missed "playback stopped" webhooks.
                    </p>
                    <InputError message={errors.stream_timeout_minutes} />
                </div>
            </section>

            <section className="space-y-6">
                <Heading
                    variant="small"
                    title="Notifications"
                    description="Post a message to a webhook when executions finish. Works with Discord, Slack, ntfy/Gotify bridges and custom automation (JSON payload)."
                />

                <div className="grid gap-2">
                    <Label htmlFor="webhook_url">Webhook URL</Label>
                    <Input
                        id="webhook_url"
                        type="url"
                        placeholder="https://discord.com/api/webhooks/…"
                        value={data.webhook_url}
                        onChange={(e) => setData('webhook_url', e.target.value)}
                    />
                    <InputError message={errors.webhook_url} />
                </div>

                <SwitchField
                    id="notify_on_failed"
                    label="Notify when an execution fails"
                    checked={data.notify_on_failed}
                    onChange={(checked) => setData('notify_on_failed', checked)}
                />
                <SwitchField
                    id="notify_on_completed"
                    label="Notify when an execution completes"
                    description="Can be noisy for large libraries."
                    checked={data.notify_on_completed}
                    onChange={(checked) =>
                        setData('notify_on_completed', checked)
                    }
                />

                <Button
                    type="button"
                    variant="outline"
                    disabled={!settings.webhook_url || isDirty}
                    title={
                        isDirty
                            ? 'Save your changes first'
                            : 'Send a test message to the saved webhook URL'
                    }
                    onClick={() =>
                        router.post(
                            testNotification.url(),
                            {},
                            { preserveScroll: true },
                        )
                    }
                >
                    <Send className="mr-1 size-4" />
                    Send test notification
                </Button>
            </section>

            <div className="flex items-center gap-4">
                <Button
                    disabled={processing}
                    data-test="update-processing-settings-button"
                >
                    Save
                </Button>
            </div>
        </form>
    );
}

function ApiAccess({
    hasApiToken,
    webhookUrls,
    webhookTokenConfigured,
}: Pick<Props, 'hasApiToken' | 'webhookUrls' | 'webhookTokenConfigured'>) {
    const { flash } = usePage();
    const newToken = flash?.apiToken;

    return (
        <section className="space-y-6">
            <Heading
                variant="small"
                title="Integrations"
                description="Endpoints for media servers and the REST API."
            />

            <div className="space-y-2">
                <Label>Playback webhooks</Label>
                <ul className="space-y-1 text-sm">
                    {Object.entries(webhookUrls).map(([server, url]) => (
                        <li
                            key={server}
                            className="flex items-center justify-between gap-2 rounded-md border px-3 py-1.5"
                        >
                            <span className="capitalize">{server}</span>
                            <span className="flex min-w-0 items-center gap-1">
                                <code className="truncate text-xs">{url}</code>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="size-6"
                                    title="Copy"
                                    onClick={() => copy(url)}
                                >
                                    <Copy className="size-3" />
                                </Button>
                            </span>
                        </li>
                    ))}
                </ul>
                <p className="text-xs text-muted-foreground">
                    {webhookTokenConfigured
                        ? 'Webhooks require the MEDIA_SERVER_WEBHOOK_TOKEN secret, sent as the X-Flowarr-Token header or a ?token= query parameter.'
                        : 'Set MEDIA_SERVER_WEBHOOK_TOKEN in the environment to require a shared secret on these endpoints.'}
                </p>
            </div>

            <div className="space-y-3">
                <Label>REST API token</Label>
                <p className="text-sm text-muted-foreground">
                    Authenticate requests to <code>/api/v1</code> with{' '}
                    <code>Authorization: Bearer &lt;token&gt;</code>. Useful for
                    dashboards like Homepage or automation scripts.
                </p>

                {newToken && (
                    <Alert>
                        <KeyRound className="size-4" />
                        <AlertTitle>Your new API token</AlertTitle>
                        <AlertDescription className="space-y-2">
                            <div className="flex items-center gap-2">
                                <code className="rounded bg-muted px-2 py-1 text-xs break-all">
                                    {newToken}
                                </code>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="size-6"
                                    title="Copy"
                                    onClick={() => copy(newToken)}
                                >
                                    <Copy className="size-3" />
                                </Button>
                            </div>
                            <span>
                                Copy it now, it will not be shown again.
                            </span>
                        </AlertDescription>
                    </Alert>
                )}

                <div className="flex gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => {
                            if (
                                hasApiToken &&
                                !confirm(
                                    'Generate a new token? The current token stops working immediately.',
                                )
                            ) {
                                return;
                            }

                            router.post(
                                generateApiToken.url(),
                                {},
                                { preserveScroll: true },
                            );
                        }}
                    >
                        <KeyRound className="mr-1 size-4" />
                        {hasApiToken ? 'Regenerate token' : 'Generate token'}
                    </Button>
                    {hasApiToken && (
                        <Button
                            type="button"
                            variant="destructive"
                            onClick={() => {
                                if (confirm('Revoke the API token?')) {
                                    router.delete(revokeApiToken.url(), {
                                        preserveScroll: true,
                                    });
                                }
                            }}
                        >
                            Revoke
                        </Button>
                    )}
                </div>
            </div>
        </section>
    );
}

export default function Processing({
    settings,
    processing,
    timezone,
    hasApiToken,
    webhookUrls,
    webhookTokenConfigured,
}: Props) {
    return (
        <div className="px-4 py-6">
            <Head title="Processing settings" />

            <h1 className="sr-only">Processing settings</h1>

            <div className="flex-1 space-y-10 md:max-w-2xl">
                <ProcessingBanner processing={processing} />

                <ProcessingForm
                    key={JSON.stringify(settings)}
                    settings={settings}
                    timezone={timezone}
                />

                <Separator />

                <ApiAccess
                    hasApiToken={hasApiToken}
                    webhookUrls={webhookUrls}
                    webhookTokenConfigured={webhookTokenConfigured}
                />
            </div>
        </div>
    );
}

Processing.layout = {
    breadcrumbs: [
        {
            title: 'Processing settings',
            href: edit(),
        },
    ],
};
