import { render, screen } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';

vi.mock('@/components/ui/sidebar', () => ({
    SidebarGroup: ({ children, className }: { children: React.ReactNode; className?: string }) => <div className={className}>{children}</div>,
    SidebarGroupContent: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
    SidebarGroupLabel: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
    SidebarMenu: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
    SidebarMenuButton: ({ children, ...props }: { children: React.ReactNode; asChild?: boolean; [key: string]: unknown }) => <a {...props}>{children}</a>,
    SidebarMenuItem: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
    useSidebar: () => ({ state: 'expanded', open: true, setOpen: vi.fn(), openMobile: false, setOpenMobile: vi.fn(), isMobile: false, toggleSidebar: vi.fn() }),
    SidebarProvider: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
    Sidebar: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
    SidebarContent: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
    SidebarFooter: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
    SidebarHeader: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
    SidebarTrigger: ({ children, ...props }: { children?: React.ReactNode; [key: string]: unknown }) => <button {...props}>{children}</button>,
    SidebarMenuAction: ({ children, ...props }: { children?: React.ReactNode; [key: string]: unknown }) => <button {...props}>{children}</button>,
}));

import { AppHeader } from '@/components/app-header';
import AppLogo from '@/components/app-logo';
import AppLogoIcon from '@/components/app-logo-icon';
import { AppShell } from '@/components/app-shell';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { Breadcrumbs } from '@/components/breadcrumbs';
import Heading from '@/components/heading';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import TextLink from '@/components/text-link';

vi.mock('@/components/ui/sheet', () => ({
    Sheet: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
    SheetTrigger: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
    SheetContent: ({ children, side }: { children: React.ReactNode; side?: string }) => <div data-side={side}>{children}</div>,
    SheetHeader: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
    SheetTitle: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
}));

vi.mock('@/components/ui/dropdown-menu', () => ({
    DropdownMenu: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
    DropdownMenuTrigger: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
    DropdownMenuContent: ({ children, ...props }: { children: React.ReactNode; [key: string]: unknown }) => <div {...props}>{children}</div>,
    DropdownMenuGroup: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
    DropdownMenuItem: ({ children, ...props }: { children: React.ReactNode; asChild?: boolean; [key: string]: unknown }) => <div {...props}>{children}</div>,
    DropdownMenuLabel: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
    DropdownMenuSeparator: () => <hr />,
}));

vi.mock('@/components/ui/navigation-menu', () => ({
    NavigationMenu: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
    NavigationMenuList: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
    NavigationMenuItem: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
    navigationMenuTriggerStyle: () => '',
}));

vi.mock('@/components/ui/tooltip', () => ({
    Tooltip: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
    TooltipTrigger: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
    TooltipContent: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
    TooltipProvider: ({ children }: { children: React.ReactNode }) => <div>{children}</div>,
}));

vi.mock('@/components/user-menu-content', () => ({
    UserMenuContent: ({ user }: { user: { name: string } }) => <div>User Menu: {user.name}</div>,
}));

vi.mock('@inertiajs/react', () => ({
    Link: ({
        href,
        children,
        ...props
    }: {
        href: string;
        children: React.ReactNode;
        [key: string]: unknown;
    }) => (
        <a href={href} {...props}>
            {children}
        </a>
    ),
    usePage: () => ({
        props: {
            auth: { user: null },
            sidebarOpen: true,
        },
        url: '/',
    }),
    router: {
        on: vi.fn(),
        cancelAll: vi.fn(),
    },
}));

describe('Heading', () => {
    it('renders title', () => {
        render(<Heading title="Dashboard" />);
        expect(screen.getByText('Dashboard')).toBeInTheDocument();
    });

    it('renders description when provided', () => {
        render(<Heading title="Dashboard" description="Manage your dashboard" />);
        expect(screen.getByText('Manage your dashboard')).toBeInTheDocument();
    });

    it('does not render description when not provided', () => {
        render(<Heading title="Dashboard" />);
        expect(screen.queryByRole('paragraph')).not.toBeInTheDocument();
    });

    it('applies small variant', () => {
        const { container } = render(
            <Heading title="Small" variant="small" />,
        );
        expect(container.querySelector('h2')).toHaveTextContent('Small');
    });
});

describe('TextLink', () => {
    it('renders as anchor with href', () => {
        render(<TextLink href="/test">Click</TextLink>);
        const link = screen.getByText('Click');
        expect(link.tagName).toBe('A');
        expect(link).toHaveAttribute('href', '/test');
    });

    it('renders children', () => {
        render(<TextLink href="/test">Link Text</TextLink>);
        expect(screen.getByText('Link Text')).toBeInTheDocument();
    });
});

describe('Breadcrumbs', () => {
    it('renders breadcrumb items', () => {
        render(
            <Breadcrumbs
                breadcrumbs={[
                    { title: 'Home', href: '/' },
                    { title: 'Settings', href: '/settings' },
                ]}
            />,
        );
        expect(screen.getByText('Home')).toBeInTheDocument();
        expect(screen.getByText('Settings')).toBeInTheDocument();
    });

    it('last item is not a link', () => {
        render(
            <Breadcrumbs
                breadcrumbs={[
                    { title: 'Home', href: '/' },
                    { title: 'Current', href: '/current' },
                ]}
            />,
        );
        const lastItem = screen.getByText('Current');
        expect(lastItem.closest('a')).toBeNull();
    });

    it('non-last items are links', () => {
        render(
            <Breadcrumbs
                breadcrumbs={[
                    { title: 'Home', href: '/' },
                    { title: 'Settings', href: '/settings' },
                ]}
            />,
        );
        const firstItem = screen.getByText('Home');
        expect(firstItem.closest('a')).toHaveAttribute('href', '/');
    });

    it('renders nothing when breadcrumbs is empty', () => {
        const { container } = render(<Breadcrumbs breadcrumbs={[]} />);
        expect(container.innerHTML).toBe('');
    });
});

describe('NavFooter', () => {
    it('renders footer links', () => {
        render(
            <NavFooter
                items={[
                    { title: 'GitHub', href: 'https://github.com/test' },
                ]}
            />,
        );
        expect(screen.getByText('GitHub')).toBeInTheDocument();
    });

    it('renders multiple items', () => {
        render(
            <NavFooter
                items={[
                    { title: 'GitHub', href: 'https://github.com/test' },
                    { title: 'Docs', href: 'https://docs.example.com' },
                ]}
            />,
        );
        expect(screen.getByText('GitHub')).toBeInTheDocument();
        expect(screen.getByText('Docs')).toBeInTheDocument();
    });
});

describe('NavMain', () => {
    it('renders nav items', () => {
        render(
            <NavMain
                items={[
                    { title: 'Dashboard', href: '/' },
                    { title: 'Settings', href: '/settings' },
                ]}
            />,
        );
        expect(screen.getByText('Dashboard')).toBeInTheDocument();
        expect(screen.getByText('Settings')).toBeInTheDocument();
    });

    it('renders group label', () => {
        render(<NavMain items={[{ title: 'Home', href: '/' }]} groupLabel="Management" />);
        expect(screen.getByText('Management')).toBeInTheDocument();
    });
});

describe('AppShell', () => {
    it('renders children', () => {
        render(<AppShell><div>Content</div></AppShell>);
        expect(screen.getByText('Content')).toBeInTheDocument();
    });

    it('renders header variant', () => {
        render(<AppShell variant="header"><div>Header Content</div></AppShell>);
        expect(screen.getByText('Header Content')).toBeInTheDocument();
    });
});

describe('AppSidebarHeader', () => {
    it('renders breadcrumbs', () => {
        render(
            <AppSidebarHeader
                breadcrumbs={[
                    { title: 'Home', href: '/' },
                    { title: 'Page', href: '/page' },
                ]}
            />,
        );
        expect(screen.getByText('Home')).toBeInTheDocument();
        expect(screen.getByText('Page')).toBeInTheDocument();
    });
});

describe('AppLogo', () => {
    it('renders Flowarr name', () => {
        render(<AppLogo />);
        expect(screen.getByText('Flowarr')).toBeInTheDocument();
    });
});

describe('AppLogoIcon', () => {
    it('renders an SVG', () => {
        const { container } = render(<AppLogoIcon />);
        expect(container.querySelector('svg')).toBeInTheDocument();
    });

    it('passes className to SVG', () => {
        const { container } = render(<AppLogoIcon className="size-5" />);
        const svg = container.querySelector('svg');
        expect(svg).toHaveAttribute('class', 'size-5');
    });
});

describe('AppHeader', () => {
    it('renders logo and dashboard links', () => {
        render(<AppHeader />);
        expect(screen.getByText('Flowarr')).toBeInTheDocument();
        expect(screen.getAllByText('Dashboard').length).toBeGreaterThanOrEqual(1);
    });

    it('renders breadcrumbs when provided', () => {
        render(
            <AppHeader
                breadcrumbs={[
                    { title: 'Home', href: '/' },
                    { title: 'Page', href: '/page' },
                ]}
            />,
        );
        expect(screen.getByText('Home')).toBeInTheDocument();
        expect(screen.getByText('Page')).toBeInTheDocument();
    });
});

describe('NavUser', () => {
    it('renders nothing when user is null', () => {
        const { container } = render(<NavUser />);
        expect(container.innerHTML).toBe('');
    });
});
