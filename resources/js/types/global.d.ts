import type { Auth } from '@/types/auth';
import type { ProcessingState } from '@/types/models';
import type { FlashToast } from '@/types/ui';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            hasUsers: boolean;
            sidebarOpen: boolean;
            processing: ProcessingState | null;
            [key: string]: unknown;
        };
        flashDataType: {
            toast?: FlashToast;
            apiToken?: string;
        };
    }
}
