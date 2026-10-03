import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export type OriginalHandling = {
    original_handling: string | null;
    replacement_start: string;
    replacement_end: string;
};

export default function OriginalHandlingFields({
    value,
    onChange,
    errors = {},
}: {
    value: OriginalHandling;
    onChange: (value: OriginalHandling) => void;
    errors?: Partial<Record<keyof OriginalHandling, string>>;
}) {
    return (
        <fieldset className="space-y-3">
            <legend className="text-sm font-medium">
                Original files after transcoding
            </legend>
            <Label htmlFor="original_handling">Handling</Label>
            <select
                id="original_handling"
                className="w-full rounded-md border bg-background p-2 text-sm"
                value={value.original_handling ?? 'legacy'}
                onChange={(event) =>
                    onChange({
                        ...value,
                        original_handling:
                            event.target.value === 'legacy'
                                ? null
                                : event.target.value,
                    })
                }
            >
                <option value="keep">
                    Keep original and separate transcoded copy
                </option>
                <option value="immediate">
                    Replace immediately after validation
                </option>
                <option value="idle">Replace when no playback is active</option>
                <option value="window">Replace between chosen hours</option>
                <option value="legacy">Use existing worker setting</option>
            </select>
            {value.original_handling === 'window' && (
                <div className="grid grid-cols-2 gap-3">
                    <div className="space-y-2">
                        <Label htmlFor="replacement_start">From</Label>
                        <Input
                            id="replacement_start"
                            type="time"
                            required
                            value={value.replacement_start}
                            onChange={(event) =>
                                onChange({
                                    ...value,
                                    replacement_start: event.target.value,
                                })
                            }
                        />
                    </div>
                    <div className="space-y-2">
                        <Label htmlFor="replacement_end">Until</Label>
                        <Input
                            id="replacement_end"
                            type="time"
                            required
                            value={value.replacement_end}
                            onChange={(event) =>
                                onChange({
                                    ...value,
                                    replacement_end: event.target.value,
                                })
                            }
                        />
                    </div>
                </div>
            )}
            <p className="text-xs text-muted-foreground">
                Replacement happens after output validation. Idle mode waits for
                all playback reported by media server webhooks to stop. Hours
                use the server timezone and may cross midnight. Pending
                replacements retain the policy chosen when encoding began.
            </p>
            {Object.entries(errors).map(
                ([key, error]) =>
                    error && (
                        <p key={key} className="text-sm text-destructive">
                            {error}
                        </p>
                    ),
            )}
        </fieldset>
    );
}
