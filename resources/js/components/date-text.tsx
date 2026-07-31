export function DateText({
    value,
    format = 'datetime',
    className,
}: {
    value: string | Date;
    format?: 'date' | 'datetime';
    className?: string;
}) {
    const date = value instanceof Date ? value : new Date(value);

    const formatted =
        format === 'date' ? date.toLocaleDateString() : date.toLocaleString();

    return (
        <span suppressHydrationWarning className={className}>
            {formatted}
        </span>
    );
}
