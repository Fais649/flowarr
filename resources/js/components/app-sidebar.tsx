import { Link } from '@inertiajs/react';
import {
    FolderGit2,
    LayoutGrid,
    ListChecks,
    Settings2,
    Telescope,
    Truck,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { edit as scanSettings } from '@/routes/config/scan';
import { index as executionsIndex } from '@/routes/executions';
import { index as librariesIndex } from '@/routes/libraries';
import { index as workersIndex } from '@/routes/workers';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const managementNavItems: NavItem[] = [
    {
        title: 'Libraries',
        href: librariesIndex(),
        icon: Telescope,
    },
    {
        title: 'Executions',
        href: executionsIndex(),
        icon: ListChecks,
    },
    {
        title: 'Workers',
        href: workersIndex(),
        icon: Truck,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Scan Settings',
        href: scanSettings(),
        icon: Settings2,
    },
    {
        title: 'GitHub',
        href: 'https://github.com/Fais649/flowarr',
        icon: FolderGit2,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                <NavMain items={managementNavItems} groupLabel="Management" />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
