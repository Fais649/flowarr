import { describe, it, expect } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Button } from '@/components/ui/button';
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Avatar, AvatarImage, AvatarFallback } from '@/components/ui/avatar';
import { Skeleton } from '@/components/ui/skeleton';
import { Spinner } from '@/components/ui/spinner';
import {
    Dialog,
    DialogTrigger,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import {
    Tooltip,
    TooltipTrigger,
    TooltipContent,
    TooltipProvider,
} from '@/components/ui/tooltip';

describe('Button', () => {
    it('renders children', () => {
        render(<Button>Click me</Button>);
        expect(screen.getByRole('button')).toHaveTextContent('Click me');
    });

    it('applies default variant', () => {
        render(<Button>Default</Button>);
        const btn = screen.getByRole('button');
        expect(btn).toHaveAttribute('data-variant', 'default');
    });

    it('applies destructive variant', () => {
        render(<Button variant="destructive">Delete</Button>);
        expect(screen.getByRole('button')).toHaveAttribute('data-variant', 'destructive');
    });

    it('applies size', () => {
        render(<Button size="sm">Small</Button>);
        expect(screen.getByRole('button')).toHaveAttribute('data-size', 'sm');
    });

    it('disabled state prevents click', async () => {
        const user = userEvent.setup();
        render(<Button disabled>Disabled</Button>);
        expect(screen.getByRole('button')).toBeDisabled();
    });
});

describe('Card', () => {
    it('renders all subcomponents', () => {
        render(
            <Card>
                <CardHeader>
                    <CardTitle>Title</CardTitle>
                    <CardDescription>Description</CardDescription>
                </CardHeader>
                <CardContent>Content</CardContent>
                <CardFooter>Footer</CardFooter>
            </Card>,
        );
        expect(screen.getByText('Title')).toBeInTheDocument();
        expect(screen.getByText('Description')).toBeInTheDocument();
        expect(screen.getByText('Content')).toBeInTheDocument();
        expect(screen.getByText('Footer')).toBeInTheDocument();
    });

    it('card has data-slot="card"', () => {
        render(<Card>Card</Card>);
        expect(screen.getByText('Card')).toHaveAttribute('data-slot', 'card');
    });
});

describe('Badge', () => {
    it('renders children', () => {
        render(<Badge>Active</Badge>);
        expect(screen.getByText('Active')).toBeInTheDocument();
    });

    it('applies variant', () => {
        render(<Badge variant="destructive">Error</Badge>);
        expect(screen.getByText('Error')).toHaveAttribute('data-slot', 'badge');
    });
});

describe('Input', () => {
    it('renders with placeholder', () => {
        render(<Input placeholder="Enter text" />);
        expect(screen.getByPlaceholderText('Enter text')).toBeInTheDocument();
    });

    it('forwards value', () => {
        render(<Input value="test" readOnly />);
        expect(screen.getByDisplayValue('test')).toBeInTheDocument();
    });

    it('disabled state', () => {
        render(<Input disabled />);
        expect(screen.getByRole('textbox')).toBeDisabled();
    });
});

describe('Avatar', () => {
    it('renders fallback', () => {
        render(
            <Avatar>
                <AvatarFallback>JD</AvatarFallback>
            </Avatar>,
        );
        expect(screen.getByText('JD')).toBeInTheDocument();
    });

    it('renders with image and falls back to fallback', () => {
        render(
            <Avatar>
                <AvatarImage src="https://example.com/avatar.png" alt="User" />
                <AvatarFallback>JD</AvatarFallback>
            </Avatar>,
        );
        expect(screen.getByText('JD')).toBeInTheDocument();
    });
});

describe('Skeleton', () => {
    it('renders with data-slot', () => {
        const { container } = render(<Skeleton className="h-4 w-full" />);
        expect(container.querySelector('[data-slot="skeleton"]')).toBeInTheDocument();
    });
});

describe('Spinner', () => {
    it('renders with Loading label', () => {
        render(<Spinner />);
        expect(screen.getByRole('status')).toHaveAttribute('aria-label', 'Loading');
    });
});

describe('Dialog', () => {
    it('renders content when open', () => {
        render(
            <Dialog defaultOpen>
                <DialogTrigger>Open</DialogTrigger>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Dialog Title</DialogTitle>
                        <DialogDescription>Dialog description</DialogDescription>
                    </DialogHeader>
                </DialogContent>
            </Dialog>,
        );
        expect(screen.getByText('Dialog Title')).toBeInTheDocument();
        expect(screen.getByText('Dialog description')).toBeInTheDocument();
    });

    it('does not render content by default', () => {
        render(
            <Dialog>
                <DialogTrigger>Open</DialogTrigger>
                <DialogContent>
                    <DialogTitle>Hidden Title</DialogTitle>
                </DialogContent>
            </Dialog>,
        );
        expect(screen.queryByText('Hidden Title')).not.toBeInTheDocument();
    });
});

describe('Tooltip', () => {
    it('renders content when open', () => {
        render(
            <TooltipProvider>
                <Tooltip defaultOpen>
                    <TooltipTrigger>Hover me</TooltipTrigger>
                    <TooltipContent>Tooltip content</TooltipContent>
                </Tooltip>
            </TooltipProvider>,
        );
        expect(screen.getByRole('tooltip')).toHaveTextContent('Tooltip content');
    });
});
