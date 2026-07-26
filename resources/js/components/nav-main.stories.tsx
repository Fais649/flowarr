import type { Meta, StoryObj } from '@storybook/react-vite';
import { LayoutGrid, Telescope } from 'lucide-react';
import { NavMain } from './nav-main';
import { SidebarProvider, Sidebar } from './ui/sidebar';

const meta: Meta<typeof NavMain> = {
    title: 'App/NavMain',
    component: NavMain,
    parameters: {
        layout: 'centered',
    },
    tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof NavMain>;

export const Default: Story = {
    render: () => (
        <SidebarProvider>
            <Sidebar collapsible="icon" className="w-64">
                <NavMain
                    items={[
                        {
                            title: 'Dashboard',
                            href: '/dashboard',
                            icon: LayoutGrid,
                        },
                        {
                            title: 'Libraries',
                            href: '/libraries',
                            icon: Telescope,
                        },
                    ]}
                    groupLabel="Platform"
                />
            </Sidebar>
        </SidebarProvider>
    ),
};

export const MultipleGroups: Story = {
    render: () => (
        <SidebarProvider>
            <Sidebar collapsible="icon" className="w-64">
                <NavMain
                    items={[
                        {
                            title: 'Dashboard',
                            href: '/dashboard',
                            icon: LayoutGrid,
                        },
                    ]}
                    groupLabel="Main"
                />
                <NavMain
                    items={[
                        {
                            title: 'Libraries',
                            href: '/libraries',
                            icon: Telescope,
                        },
                    ]}
                    groupLabel="Management"
                />
            </Sidebar>
        </SidebarProvider>
    ),
};
