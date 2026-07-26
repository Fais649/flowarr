import type { Meta, StoryObj } from '@storybook/react';
import { Breadcrumbs } from './breadcrumbs';
import { SidebarProvider } from './ui/sidebar';

const meta: Meta<typeof Breadcrumbs> = {
    title: 'App/Breadcrumbs',
    component: Breadcrumbs,
    parameters: {
        layout: 'padded',
    },
    tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof Breadcrumbs>;

export const SingleLevel: Story = {
    render: () => (
        <SidebarProvider>
            <Breadcrumbs
                breadcrumbs={[{ title: 'Dashboard', href: '/dashboard' }]}
            />
        </SidebarProvider>
    ),
};

export const MultiLevel: Story = {
    render: () => (
        <SidebarProvider>
            <Breadcrumbs
                breadcrumbs={[
                    { title: 'Dashboard', href: '/dashboard' },
                    { title: 'Settings', href: '/settings' },
                    { title: 'Profile', href: '/settings/profile' },
                ]}
            />
        </SidebarProvider>
    ),
};
