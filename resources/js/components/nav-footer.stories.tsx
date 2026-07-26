import type { Meta, StoryObj } from '@storybook/react';
import { FolderGit2 } from 'lucide-react';
import { NavFooter } from './nav-footer';
import { SidebarProvider, Sidebar } from './ui/sidebar';

const meta: Meta<typeof NavFooter> = {
    title: 'App/NavFooter',
    component: NavFooter,
    parameters: {
        layout: 'centered',
    },
    tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof NavFooter>;

export const SingleItem: Story = {
    render: () => (
        <SidebarProvider>
            <Sidebar collapsible="icon" className="w-64">
                <NavFooter
                    items={[
                        {
                            title: 'GitHub',
                            href: 'https://github.com',
                            icon: FolderGit2,
                        },
                    ]}
                />
            </Sidebar>
        </SidebarProvider>
    ),
};

export const MultipleItems: Story = {
    render: () => (
        <SidebarProvider>
            <Sidebar collapsible="icon" className="w-64">
                <NavFooter
                    items={[
                        {
                            title: 'GitHub',
                            href: 'https://github.com',
                            icon: FolderGit2,
                        },
                        {
                            title: 'Docs',
                            href: 'https://docs.example.com',
                            icon: FolderGit2,
                        },
                    ]}
                />
            </Sidebar>
        </SidebarProvider>
    ),
};
