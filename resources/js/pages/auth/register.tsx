import { Head } from '@inertiajs/react';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import PasskeyRegistration from '@/components/passkey-register';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { home } from '@/routes';

type Props = {
    passwordRules: string;
};

type FormData = {
    name: string;
    email: string;
    password: string;
    password_confirmation: string;
};

export default function Register({ passwordRules }: Props) {
    const [step, setStep] = useState(0);
    const [formData, setFormData] = useState<FormData>({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });
    const [submitting, setSubmitting] = useState(false);
    const [accountCreated, setAccountCreated] = useState(false);
    const [showPasskey, setShowPasskey] = useState(true);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const updateField = (field: keyof FormData, value: string) => {
        setFormData((prev) => ({ ...prev, [field]: value }));
        setErrors((prev) => ({ ...prev, [field]: '' }));
    };

    const validateStep1 = (): boolean => {
        const newErrors: Record<string, string> = {};

        if (!formData.name.trim()) {
            newErrors.name = 'Name is required.';
        }

        if (!formData.email.trim()) {
            newErrors.email = 'Email is required.';
        } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formData.email)) {
            newErrors.email = 'Please enter a valid email address.';
        }

        if (!formData.password) {
            newErrors.password = 'Password is required.';
        } else if (formData.password.length < 8) {
            newErrors.password = 'Password must be at least 8 characters.';
        }

        if (formData.password !== formData.password_confirmation) {
            newErrors.password_confirmation = 'Passwords do not match.';
        }

        setErrors(newErrors);

        return Object.keys(newErrors).length === 0;
    };

    const handleCreateAccount = async () => {
        setSubmitting(true);
        setErrors({});

        try {
            const token =
                document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute('content') ?? '';

            const response = await fetch('/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(formData),
            });

            if (!response.ok) {
                const body = await response.json();

                if (body.errors) {
                    setErrors(body.errors);
                } else {
                    setErrors({ form: body.message ?? 'Registration failed.' });
                }

                return;
            }

            setAccountCreated(true);

            if (showPasskey) {
                setStep(2);
            } else {
                router.visit(home());
            }
        } catch {
            setErrors({
                form: 'An unexpected error occurred. Please try again.',
            });
        } finally {
            setSubmitting(false);
        }
    };

    if (accountCreated && !showPasskey) {
        return null;
    }

    return (
        <>
            <Head title="Set up your admin account" />

            {step === 0 && (
                <Card className="w-full max-w-md">
                    <CardHeader className="text-center">
                        <CardTitle className="text-xl">
                            Welcome to Flowarr
                        </CardTitle>
                        <CardDescription>
                            Set up your admin account to get started.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                type="text"
                                required
                                autoFocus
                                value={formData.name}
                                onChange={(e) =>
                                    updateField('name', e.target.value)
                                }
                                placeholder="Full name"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email address</Label>
                            <Input
                                id="email"
                                type="email"
                                required
                                value={formData.email}
                                onChange={(e) =>
                                    updateField('email', e.target.value)
                                }
                                placeholder="email@example.com"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password">Password</Label>
                            <PasswordInput
                                id="password"
                                required
                                value={formData.password}
                                onChange={(e) =>
                                    updateField('password', e.target.value)
                                }
                                placeholder="Password"
                                passwordrules={passwordRules}
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="password_confirmation">
                                Confirm password
                            </Label>
                            <PasswordInput
                                id="password_confirmation"
                                required
                                value={formData.password_confirmation}
                                onChange={(e) =>
                                    updateField(
                                        'password_confirmation',
                                        e.target.value,
                                    )
                                }
                                placeholder="Confirm password"
                                passwordrules={passwordRules}
                            />
                            <InputError
                                message={errors.password_confirmation}
                            />
                        </div>

                        <Button
                            type="button"
                            className="w-full"
                            onClick={() => {
                                if (validateStep1()) {
                                    setStep(1);
                                }
                            }}
                        >
                            Continue
                        </Button>
                    </CardContent>
                </Card>
            )}

            {step === 1 && (
                <Card className="w-full max-w-md">
                    <CardHeader className="text-center">
                        <CardTitle className="text-xl">
                            Review your details
                        </CardTitle>
                        <CardDescription>
                            Confirm your account information below.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-2 rounded-lg bg-muted/50 p-4 text-sm">
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Name
                                </span>
                                <span>{formData.name}</span>
                            </div>
                            <div className="flex justify-between">
                                <span className="text-muted-foreground">
                                    Email
                                </span>
                                <span>{formData.email}</span>
                            </div>
                        </div>

                        <div className="flex items-center gap-2">
                            <input
                                id="setup-passkey"
                                type="checkbox"
                                checked={showPasskey}
                                onChange={(e) =>
                                    setShowPasskey(e.target.checked)
                                }
                                className="h-4 w-4 rounded border-foreground/20"
                            />
                            <Label htmlFor="setup-passkey" className="text-sm">
                                Set up a passkey after creating my account
                            </Label>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            Passkeys let you sign in quickly with your device's
                            built-in authentication (fingerprint, face scan, or
                            PIN).
                        </p>

                        <InputError message={errors.form} />

                        <div className="flex gap-2">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setStep(0)}
                                disabled={submitting}
                            >
                                Back
                            </Button>
                            <Button
                                type="button"
                                className="flex-1"
                                onClick={handleCreateAccount}
                                disabled={submitting}
                                data-test="register-user-button"
                            >
                                {submitting && <Spinner />}
                                Create account
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            )}

            {step === 2 && accountCreated && (
                <Card className="w-full max-w-md">
                    <CardHeader className="text-center">
                        <CardTitle className="text-xl">
                            Set up a passkey
                        </CardTitle>
                        <CardDescription>
                            Add a passkey for faster, password-free login.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <PasskeyRegistration
                            onSuccess={() => router.visit(home())}
                        />
                        <Button
                            type="button"
                            variant="ghost"
                            className="w-full"
                            onClick={() => router.visit(home())}
                        >
                            Skip for now
                        </Button>
                    </CardContent>
                </Card>
            )}
        </>
    );
}

Register.layout = {
    title: 'Set up your admin account',
    description: 'Create your admin account to start using Flowarr',
};
