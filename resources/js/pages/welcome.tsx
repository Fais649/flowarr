import { Head, Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { login, register } from '@/routes';

export default function Welcome() {
    return (
        <>
            <Head title="Flowarr" />

            <div className="flex min-h-screen flex-col items-center justify-center gap-8 bg-gradient-to-br from-background to-violet-50/50 p-6 dark:from-[#0d0d1a] dark:to-[#0d0d1a]">
                <div className="flex flex-col items-center gap-4">
                    <div className="flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-violet-500 to-violet-700 shadow-lg">
                        <AppLogoIcon className="size-10 fill-current text-white" />
                    </div>
                    <h1 className="text-2xl font-bold text-foreground">
                        Flowarr
                    </h1>
                    <p className="text-center text-sm text-muted-foreground">
                        Media library transformation automation
                    </p>
                </div>

                <div className="flex gap-4">
                    <Link
                        href={login()}
                        className="inline-flex items-center rounded-lg bg-primary px-6 py-2.5 text-sm font-medium text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                    >
                        Log in
                    </Link>
                    <Link
                        href={register()}
                        className="inline-flex items-center rounded-lg border border-input bg-background px-6 py-2.5 text-sm font-medium text-foreground shadow-sm transition-colors hover:bg-accent"
                    >
                        Register
                    </Link>
                </div>
            </div>
        </>
    );
}
