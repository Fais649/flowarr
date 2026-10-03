import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { afterEach, expect, it, vi } from 'vitest';
import DirectoryBrowser from '@/components/directory-browser';

afterEach(() => vi.unstubAllGlobals());

it('loads children on expansion and selects a deeply nested folder', async () => {
    const fetch = vi.fn().mockImplementation(async (url: string) => {
        const path = new URL(url, 'http://localhost').searchParams.get('path')!;
        const level = path.split('/').filter(Boolean).length;

        return {
            ok: true,
            json: async () => ({
                directories:
                    level < 8
                        ? [
                              {
                                  name: `folder${level}`,
                                  path: `${path === '/' ? '' : path}/folder${level}`,
                              },
                          ]
                        : [],
            }),
        };
    });
    vi.stubGlobal('fetch', fetch);
    const select = vi.fn();
    render(<DirectoryBrowser open onOpenChange={vi.fn()} onSelect={select} />);

    for (let i = 0; i < 7; i++) {
        fireEvent.click(
            await screen.findByRole('button', { name: `Expand folder${i}` }),
        );
    }

    fireEvent.click(await screen.findByText('folder7'));
    fireEvent.click(screen.getByRole('button', { name: 'Select Directory' }));
    expect(select).toHaveBeenCalledWith(
        '/folder0/folder1/folder2/folder3/folder4/folder5/folder6/folder7',
    );
    expect(fetch).toHaveBeenCalledTimes(8);
});

it('allows retrying a failed child request', async () => {
    const fetch = vi
        .fn()
        .mockResolvedValueOnce({
            ok: true,
            json: async () => ({
                directories: [{ name: 'media', path: '/media' }],
            }),
        })
        .mockResolvedValueOnce({ ok: false })
        .mockResolvedValueOnce({
            ok: true,
            json: async () => ({
                directories: [{ name: 'tv', path: '/media/tv' }],
            }),
        });
    vi.stubGlobal('fetch', fetch);
    render(<DirectoryBrowser open onOpenChange={vi.fn()} onSelect={vi.fn()} />);
    fireEvent.click(
        await screen.findByRole('button', { name: 'Expand media' }),
    );
    await screen.findByRole('alert');
    await waitFor(() =>
        expect(
            screen.getByRole('button', { name: 'Expand media' }),
        ).toBeEnabled(),
    );
    fireEvent.click(screen.getByRole('button', { name: 'Expand media' }));
    expect(await screen.findByText('tv')).toBeInTheDocument();
});
