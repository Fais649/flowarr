import { Link } from '@inertiajs/react';

type SettingsTab = 'user' | 'config';

const userItems = [
    { title: 'Profile', href: '/settings/profile' },
    { title: 'Security', href: '/settings/security' },
    { title: 'Appearance', href: '/settings/appearance' },
];

const configItems = [
    { title: 'Workers', href: '/settings/workers' },
    { title: 'Scan', href: '/settings/scan' },
];

const allItems = [...userItems, ...configItems];

function resolveTab(path: string): SettingsTab {
    const item = allItems.find((i) => i.href === path);

    if (item && userItems.includes(item)) {
        return 'user';
    }

    return 'config';
}

export function SettingsNav({ currentPath }: { currentPath: string }) {
    const activeTab = resolveTab(currentPath);
    const activeItems = activeTab === 'user' ? userItems : configItems;

    return (
        <div className="mb-6 space-y-4">
            {/* Primary tabs */}
            <div className="flex gap-1 rounded-lg bg-muted p-1">
                <Link
                    href="/settings/profile"
                    className={`flex-1 rounded-md px-3 py-2 text-center text-sm font-medium transition-colors ${
                        activeTab === 'user'
                            ? 'bg-background text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground'
                    }`}
                >
                    User Settings
                </Link>
                <Link
                    href="/settings/workers"
                    className={`flex-1 rounded-md px-3 py-2 text-center text-sm font-medium transition-colors ${
                        activeTab === 'config'
                            ? 'bg-background text-foreground shadow-sm'
                            : 'text-muted-foreground hover:text-foreground'
                    }`}
                >
                    Configuration
                </Link>
            </div>

            {/* Sub-navigation for the active tab */}
            <div className="flex gap-4 border-b">
                {activeItems.map((item) => {
                    const isActive = currentPath === item.href;

                    return (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={`px-1 pb-3 text-sm font-medium transition-colors ${
                                isActive
                                    ? 'border-b-2 border-primary text-foreground'
                                    : 'text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            {item.title}
                        </Link>
                    );
                })}
            </div>
        </div>
    );
}
