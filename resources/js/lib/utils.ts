import type { InertiaLinkProps } from '@inertiajs/react';
import { clsx } from 'clsx';
import type { ClassValue } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs: ClassValue[]) {
    return twMerge(clsx(inputs));
}

export function toUrl(url: NonNullable<InertiaLinkProps['href']>): string {
    return typeof url === 'string' ? url : url.url;
}

export function toDateString(value: string | undefined | null): string {
    if (!value) {
        return 'Never';
    }

    // Accepts ISO-8601 strings as well as legacy unix timestamps (seconds).
    const date = /^\d+$/.test(value)
        ? new Date(Number(value) * 1000)
        : new Date(value);
    const formattedDate = new Intl.DateTimeFormat('en-US', {
        month: 'long',
        day: 'numeric',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    }).format(date);

    return formattedDate;
}

export function formatDuration(
    totalSeconds: number | null | undefined,
): string {
    if (totalSeconds === null || totalSeconds === undefined) {
        return '-';
    }

    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor((totalSeconds % 3600) / 60);
    const seconds = Math.floor(totalSeconds % 60);

    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    }

    if (minutes > 0) {
        return `${minutes}m ${seconds}s`;
    }

    return `${seconds}s`;
}

export function fileName(path: string): string {
    return path.split('/').pop() || path;
}
