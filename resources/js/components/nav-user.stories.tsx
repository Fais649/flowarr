import type { Meta, StoryObj } from '@storybook/react';
import { NavUser } from './nav-user';
import { Sidebar, SidebarProvider } from './ui/sidebar';

const meta: Meta<typeof NavUser> = {
    title: 'App/NavUser',
    component: NavUser,
    parameters: {
        layout: 'centered',
    },
    tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof NavUser>;

export const Default: Story = {
    render: () => (
        <SidebarProvider defaultOpen={true}>
            <Sidebar collapsible="icon" className="w-64">
                <NavUser />
            </Sidebar>
        </SidebarProvider>
    ),
};
