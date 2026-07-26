import type { Meta, StoryObj } from '@storybook/react';
import { useState } from 'react';
import DirectoryBrowser from '@/components/directory-browser';

const meta: Meta<typeof DirectoryBrowser> = {
    title: 'Components/DirectoryBrowser',
    component: DirectoryBrowser,
    parameters: {
        layout: 'centered',
    },
    tags: ['autodocs'],
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
