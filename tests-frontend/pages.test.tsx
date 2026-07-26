import { render, screen } from '@testing-library/react';
import { describe, it, expect, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
    Head: () => null,
    Link: ({ href, children, ...props }: { href: string; children: React.ReactNode; [key: string]: unknown }) => (
        <a href={href} {...props}>{children}</a>
    ),
    usePage: () => ({
        props: {
            auth: { user: { name: 'Test User', email: 'test@example.com' } },
            sidebarOpen: true,
        },
        url: '/',
    }),
    router: {
        on: vi.fn(),
        cancelAll: vi.fn(),
        delete: vi.fn(),
        reload: vi.fn(),
        flushAll: vi.fn(),
        visit: vi.fn(),
    },
    Form: ({ children, ...props }: { children: (args: { resetAndClearErrors: () => void; processing: boolean; errors: Record<string, string> }) => React.ReactNode; [key: string]: unknown }) => (
        <form {...props}>{children({ resetAndClearErrors: vi.fn(), processing: false, errors: {} })}</form>
    ),
}));

import ForgotPassword from '@/pages/auth/forgot-password';
import Login from '@/pages/auth/login';
import Register from '@/pages/auth/register';
import ResetPassword from '@/pages/auth/reset-password';
import Dashboard from '@/pages/dashboard';
import Appearance from '@/pages/settings/appearance';
import Profile from '@/pages/settings/profile';
import Security from '@/pages/settings/security';
import Welcome from '@/pages/welcome';

describe('Login', () => {
    it('renders form fields', () => {
        render(<Login status={undefined} canResetPassword />);
        expect(screen.getByText('Email address')).toBeInTheDocument();
        expect(screen.getByText('Password')).toBeInTheDocument();
        expect(screen.getByText('Remember me')).toBeInTheDocument();
    });

    it('renders log in button', () => {
        render(<Login status={undefined} canResetPassword />);
        expect(screen.getByText('Log in')).toBeInTheDocument();
    });

    it('renders forgot password link when canResetPassword', () => {
        render(<Login status={undefined} canResetPassword />);
        expect(screen.getByText('Forgot your password?')).toBeInTheDocument();
    });

    it('renders status message', () => {
        render(<Login status="Verification link sent!" canResetPassword />);
        expect(screen.getByText('Verification link sent!')).toBeInTheDocument();
    });
});

describe('Register', () => {
    it('renders form fields on step 0', () => {
        render(<Register passwordRules="" />);
        expect(screen.getByText('Name')).toBeInTheDocument();
        expect(screen.getByText('Email address')).toBeInTheDocument();
        expect(screen.getByText('Password')).toBeInTheDocument();
        expect(screen.getByText('Confirm password')).toBeInTheDocument();
    });

    it('renders Continue button on step 0', () => {
        render(<Register passwordRules="" />);
        expect(screen.getByText('Continue')).toBeInTheDocument();
    });
});

describe('ForgotPassword', () => {
    it('renders email field', () => {
        render(<ForgotPassword status={undefined} />);
        expect(screen.getByText('Email address')).toBeInTheDocument();
        expect(screen.getByText('Email password reset link')).toBeInTheDocument();
    });

    it('renders status message', () => {
        render(<ForgotPassword status="Reset link sent!" />);
        expect(screen.getByText('Reset link sent!')).toBeInTheDocument();
    });
});

describe('ResetPassword', () => {
    it('renders form fields', () => {
        render(<ResetPassword token="abc" email="test@example.com" passwordRules="" />);
        expect(screen.getByDisplayValue('test@example.com')).toBeInTheDocument();
        expect(screen.getByText('Reset password')).toBeInTheDocument();
    });
});

describe('Profile', () => {
    it('renders profile form', () => {
        render(<Profile />);
        expect(screen.getByText('Profile')).toBeInTheDocument();
        expect(screen.getByText('Save')).toBeInTheDocument();
    });

    it('renders delete user section', () => {
        render(<Profile />);
        expect(screen.getAllByText('Delete account').length).toBeGreaterThanOrEqual(1);
    });
});

describe('Security', () => {
    it('renders password form', () => {
        render(<Security passwordRules="" canManagePasskeys={false} passkeys={[]} />);
        expect(screen.getByText('Update password')).toBeInTheDocument();
        expect(screen.getByText('Save')).toBeInTheDocument();
    });
});

describe('Appearance', () => {
    it('renders heading', () => {
        render(<Appearance />);
        expect(screen.getAllByText('Appearance settings').length).toBeGreaterThanOrEqual(1);
    });
});

describe('Dashboard', () => {
    const baseProps = {
        metrics: { libraryCount: 3, pendingExecutions: 5, failedToday: 1, processingCount: 2 },
        recentExecutions: [
            { id: 1, file_path: '/movies/test.mp4', status: 'completed', library: 'Movies', job_type: 'transcode', created_at: '2026-07-26T10:00:00Z' },
        ],
        libraries: [
            { id: 1, base_path: '/media/movies', status: 'active', enabled_jobs: 3, last_scan: '2026-07-25T10:00:00Z' },
        ],
    };

    it('renders metric cards', () => {
        render(<Dashboard {...baseProps} />);
        expect(screen.getByText('Libraries')).toBeInTheDocument();
        expect(screen.getByText('Pending')).toBeInTheDocument();
    });

    it('renders recent executions', () => {
        render(<Dashboard {...baseProps} />);
        expect(screen.getByText('Recent Executions')).toBeInTheDocument();
    });

    it('renders library health', () => {
        render(<Dashboard {...baseProps} />);
        expect(screen.getByText('Library Health')).toBeInTheDocument();
    });

    it('renders empty state when no libraries', () => {
        render(<Dashboard {...baseProps} libraries={[]} recentExecutions={[]} />);
        expect(screen.getByText('No libraries configured')).toBeInTheDocument();
    });
});

describe('Welcome', () => {
    it('renders branding', () => {
        render(<Welcome />);
        expect(screen.getByText('Flowarr')).toBeInTheDocument();
        expect(screen.getByText('Media library transformation automation')).toBeInTheDocument();
    });

    it('renders login and register links', () => {
        render(<Welcome />);
        expect(screen.getByText('Log in')).toBeInTheDocument();
        expect(screen.getByText('Register')).toBeInTheDocument();
    });
});
