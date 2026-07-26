import type { Meta, StoryObj } from '@storybook/react';
import AppLogo from './app-logo';

const meta: Meta<typeof AppLogo> = {
    title: 'App/AppLogo',
    component: AppLogo,
    parameters: {
        layout: 'centered',
    },
    tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof AppLogo>;

export const Default: Story = {
    render: () => (
        <div className="flex items-center gap-2">
            <AppLogo />
        </div>
    ),
};
