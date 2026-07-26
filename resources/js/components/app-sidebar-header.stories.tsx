import type { Meta, StoryObj } from '@storybook/react-vite';
import { AppSidebarHeader } from './app-sidebar-header';
import { SidebarProvider, Sidebar } from './ui/sidebar';

const meta: Meta<typeof AppSidebarHeader> = {
    title: 'App/AppSidebarHeader',
    component: AppSidebarHeader,
    parameters: {
        layout: 'fullscreen',
    },
    tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof AppSidebarHeader>;

export const Default: Story = {
    render: () => (
        <SidebarProvider>
            <Sidebar collapsible="icon" />
            <AppSidebarHeader
                breadcrumbs={[
                    { title: 'Dashboard', href: '/dashboard' },
                    { title: 'Settings', href: '/settings' },
                ]}
            />
        </SidebarProvider>
    ),
};
