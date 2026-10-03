import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import DirectoryBrowser from '@/components/directory-browser';

const meta: Meta<typeof DirectoryBrowser> = {
    title: 'Components/DirectoryBrowser',
    component: DirectoryBrowser,
    parameters: {
        layout: 'centered',
        docs: {
            description: {
                component:
                    'Loads child folders on expansion, including paths deeper than five levels.',
            },
        },
    },
    tags: ['autodocs'],
    beforeEach: () => {
        const originalFetch = globalThis.fetch;
        globalThis.fetch = async (input, options) => {
            const url = new URL(String(input), window.location.origin);

            if (url.pathname !== '/libraries/directories') {
                return originalFetch(input, options);
            }

            const path = url.searchParams.get('path') ?? '/';
            const directories =
                path === '/'
                    ? [{ name: 'media', path: '/media' }]
                    : path === '/media'
                      ? [
                            { name: 'TV', path: '/media/TV' },
                            { name: 'Movies', path: '/media/Movies' },
                        ]
                      : path === '/media/TV'
                        ? [
                              {
                                  name: 'Grace and Frankie',
                                  path: '/media/TV/Grace and Frankie',
                              },
                          ]
                        : [];

            return new Response(JSON.stringify({ path, directories }), {
                headers: { 'Content-Type': 'application/json' },
            });
        };

        return () => {
            globalThis.fetch = originalFetch;
        };
    },
};

export default meta;
type Story = StoryObj<typeof DirectoryBrowser>;

export const Open: Story = {
    render: function Render() {
        const [open, setOpen] = useState(true);

        return (
            <DirectoryBrowser
                open={open}
                onOpenChange={setOpen}
                onSelect={(path) => console.log('Selected:', path)}
            />
        );
    },
};
