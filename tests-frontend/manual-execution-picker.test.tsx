import { fireEvent, render, screen } from '@testing-library/react';
import { afterEach, expect, it, vi } from 'vitest';
import ManualExecutionPicker from '@/components/manual-execution-picker';

const { post } = vi.hoisted(() => ({ post: vi.fn() }));
vi.mock('@inertiajs/react', async () => {
    const { useState } = await import('react');

    return {
        useForm: (initial: Record<string, unknown>) => {
            const [data, setData] = useState(initial);
            let transform = (value: Record<string, unknown>) => value;

            return {
                data,
                errors: {},
                processing: false,
                setData: (
                    key: string | Record<string, unknown>,
                    value?: unknown,
                ) =>
                    setData((current) =>
                        typeof key === 'string'
                            ? { ...current, [key]: value }
                            : key,
                    ),
                transform: (callback: typeof transform) => {
                    transform = callback;
                },
                post: (url: string) => post(url, transform(data)),
            };
        },
    };
});

afterEach(() => {
    vi.unstubAllGlobals();
    post.mockClear();
});
const libraries = [
    {
        id: 1,
        base_path: '/media',
        workers: [{ job_type: 'transcode_media', enabled: true }],
    },
];

it.each(['Enqueue', 'Run now'])(
    'selects files and submits %s with the correct mode',
    async (action) => {
        vi.stubGlobal(
            'fetch',
            vi
                .fn()
                .mockResolvedValue({
                    ok: true,
                    json: async () => ({
                        parent: null,
                        entries: [
                            {
                                name: 'episode.mkv',
                                path: '/media/episode.mkv',
                                directory: false,
                            },
                        ],
                    }),
                }),
        );
        render(<ManualExecutionPicker libraries={libraries} />);
        fireEvent.click(screen.getByText('Select files to execute'));
        fireEvent.click(
            await screen.findByRole('checkbox', { name: 'episode.mkv' }),
        );
        fireEvent.click(
            screen.getByRole('button', { name: action }),
        );
        expect(post).toHaveBeenCalledWith(
            '/executions/manual',
            expect.objectContaining({
                files: ['/media/episode.mkv'],
                mode: action === 'Enqueue' ? 'enqueue' : 'now',
            }),
        );
    },
);

it('preserves selections while browsing subdirectories', async () => {
    const fetch = vi.fn().mockImplementation(async (url: string) => ({
        ok: true,
        json: async () =>
            url.includes('path=')
                ? {
                      parent: '/media',
                      entries: [
                          {
                              name: 'episode2.mkv',
                              path: '/media/Season/episode2.mkv',
                              directory: false,
                          },
                      ],
                  }
                : {
                      parent: null,
                      entries: [
                          {
                              name: 'Season',
                              path: '/media/Season',
                              directory: true,
                          },
                          {
                              name: 'episode.mkv',
                              path: '/media/episode.mkv',
                              directory: false,
                          },
                      ],
                  },
    }));
    vi.stubGlobal('fetch', fetch);
    render(<ManualExecutionPicker libraries={libraries} />);
    fireEvent.click(screen.getByText('Select files to execute'));
    fireEvent.click(
        await screen.findByRole('checkbox', { name: 'episode.mkv' }),
    );
    fireEvent.click(screen.getByRole('button', { name: 'Season' }));
    fireEvent.click(
        await screen.findByRole('checkbox', { name: 'episode2.mkv' }),
    );
    expect(screen.getByText(/2 file\(s\) selected/)).toBeInTheDocument();
});
