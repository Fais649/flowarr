import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, it, expect, vi } from 'vitest';

vi.mock('@inertiajs/react', () => ({
    Link: ({ href, children, ...props }: { href: string; children: React.ReactNode; [key: string]: unknown }) => (
        <a href={href} {...props}>{children}</a>
    ),
    usePage: () => ({ props: { auth: { user: null } }, url: '/' }),
    router: { on: vi.fn(), cancelAll: vi.fn(), delete: vi.fn(), reload: vi.fn(), flushAll: vi.fn(), visit: vi.fn() },
    Form: ({ children, ...props }: { children: (args: { resetAndClearErrors: () => void; processing: boolean; errors: Record<string, string> }) => React.ReactNode; [key: string]: unknown }) => (
        <form {...props}>{children({ resetAndClearErrors: vi.fn(), processing: false, errors: {} })}</form>
    ),
}));

vi.mock('@laravel/passkeys/react', () => ({
    usePasskeyRegister: () => ({ register: vi.fn(), isLoading: false, error: null, isSupported: true }),
    usePasskeyVerify: () => ({ verify: vi.fn(), isLoading: false, error: null, isSupported: true }),
}));

import AlertError from '@/components/alert-error';
import DeleteUser from '@/components/delete-user';
import InputError from '@/components/input-error';
import ManagePasskeys from '@/components/manage-passkeys';
import PasskeyItem from '@/components/passkey-item';
import PasskeyRegistration from '@/components/passkey-register';
import PasskeyVerify from '@/components/passkey-verify';
import PasswordInput from '@/components/password-input';

describe('PasswordInput', () => {
    it('renders password input', () => {
        render(<PasswordInput placeholder="Enter password" />);
        expect(screen.getByPlaceholderText('Enter password')).toHaveAttribute('type', 'password');
    });

    it('toggles visibility on button click', async () => {
        const user = userEvent.setup();
        render(<PasswordInput placeholder="Password" />);
        const input = screen.getByPlaceholderText('Password');
        expect(input).toHaveAttribute('type', 'password');
        await user.click(screen.getByLabelText('Show password'));
        expect(input).toHaveAttribute('type', 'text');
        await user.click(screen.getByLabelText('Hide password'));
        expect(input).toHaveAttribute('type', 'password');
    });

    it('forwards ref', () => {
        const ref = { current: null };
        render(<PasswordInput ref={ref} placeholder="Password" />);
        expect(ref.current).toBeInstanceOf(HTMLInputElement);
    });
});

describe('InputError', () => {
    it('renders message when provided', () => {
        render(<InputError message="Error message" />);
        expect(screen.getByText('Error message')).toBeInTheDocument();
    });

    it('renders nothing when no message', () => {
        const { container } = render(<InputError />);
        expect(container.innerHTML).toBe('');
    });
});

describe('AlertError', () => {
    it('renders error messages', () => {
        render(<AlertError errors={['Error 1', 'Error 2']} />);
        expect(screen.getByText('Error 1')).toBeInTheDocument();
        expect(screen.getByText('Error 2')).toBeInTheDocument();
    });

    it('renders default title', () => {
        render(<AlertError errors={['Error']} />);
        expect(screen.getByText('Something went wrong.')).toBeInTheDocument();
    });

    it('renders custom title', () => {
        render(<AlertError errors={['Error']} title="Custom title" />);
        expect(screen.getByText('Custom title')).toBeInTheDocument();
    });
});

describe('DeleteUser', () => {
    it('renders heading and warning', () => {
        render(<DeleteUser />);
        expect(screen.getAllByText('Delete account').length).toBeGreaterThanOrEqual(1);
        expect(screen.getByText('Warning')).toBeInTheDocument();
    });
});

describe('PasskeyRegistration', () => {
    it('renders add passkey button initially', () => {
        render(<PasskeyRegistration onSuccess={vi.fn()} />);
        expect(screen.getByText('Add passkey')).toBeInTheDocument();
    });

    it('renders form after clicking add', async () => {
        const user = userEvent.setup();
        render(<PasskeyRegistration onSuccess={vi.fn()} />);
        await user.click(screen.getByText('Add passkey'));
        expect(screen.getByText('Register passkey')).toBeInTheDocument();
    });
});

describe('PasskeyVerify', () => {
    it('renders sign in button', () => {
        render(<PasskeyVerify />);
        expect(screen.getByText('Sign in with a passkey')).toBeInTheDocument();
    });

    it('renders custom label', () => {
        render(<PasskeyVerify label="Custom label" />);
        expect(screen.getByText('Custom label')).toBeInTheDocument();
    });
});

describe('PasskeyItem', () => {
    it('renders passkey name', () => {
        render(
            <PasskeyItem
                passkey={{ id: 1, name: 'My Key', created_at_diff: '2 days ago', last_used_at_diff: null, authenticator: null }}
                onDelete={vi.fn()}
            />,
        );
        expect(screen.getByText('My Key')).toBeInTheDocument();
    });

    it('calls onDelete when remove confirmed', async () => {
        const onDelete = vi.fn();
        const user = userEvent.setup();
        render(
            <PasskeyItem
                passkey={{ id: 1, name: 'My Key', created_at_diff: '2 days ago', last_used_at_diff: null, authenticator: null }}
                onDelete={onDelete}
            />,
        );
        await user.click(screen.getByRole('button', { name: /remove/i }));
        expect(screen.getAllByText('Remove passkey').length).toBeGreaterThanOrEqual(1);
    });
});

describe('ManagePasskeys', () => {
    it('renders nothing when canManagePasskeys is false', () => {
        const { container } = render(<ManagePasskeys canManagePasskeys={false} />);
        expect(container.innerHTML).toBe('');
    });

    it('renders empty state when no passkeys', () => {
        render(<ManagePasskeys canManagePasskeys passkeys={[]} />);
        expect(screen.getByText('No passkeys yet')).toBeInTheDocument();
    });

    it('renders passkey list', () => {
        render(
            <ManagePasskeys
                canManagePasskeys
                passkeys={[
                    { id: 1, name: 'My Key', created_at_diff: '2 days ago', last_used_at_diff: null, authenticator: null },
                ]}
            />,
        );
        expect(screen.getByText('My Key')).toBeInTheDocument();
    });
});
