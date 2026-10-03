import type { Meta, StoryObj } from '@storybook/react-vite';
import { useState } from 'react';
import OriginalHandlingFields from './original-handling-fields';
const meta = {
    title: 'Components/OriginalHandlingFields',
    component: OriginalHandlingFields,
} satisfies Meta<typeof OriginalHandlingFields>;
export default meta;
type Story = StoryObj<typeof meta>;
export const Window: Story = {
    args: {
        value: {
            original_handling: 'window',
            replacement_start: '22:00',
            replacement_end: '06:00',
        },
        onChange: () => {},
    },
    render: function Render(args) {
        const [value, setValue] = useState(args.value);

        return (
            <div className="max-w-lg p-6">
                <OriginalHandlingFields value={value} onChange={setValue} />
            </div>
        );
    },
};
export const Keep: Story = {
    ...Window,
    args: {
        value: {
            original_handling: 'keep',
            replacement_start: '',
            replacement_end: '',
        },
        onChange: () => {},
    },
};
