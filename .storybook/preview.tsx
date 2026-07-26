import { withThemeByClassName } from '@storybook/addon-themes';
import type { Preview } from '@storybook/react-vite';
import '../resources/css/app.css';

const preview: Preview = {
    parameters: {
        controls: {
            matchers: {
                color: /(background|color)$/i,
                date: /Date$/i,
            },
        },

        a11y: {
            test: 'todo',
        },
    },

    decorators: [
        withThemeByClassName({
            themes: {
                light: '',
                dark: 'dark',
            },
            defaultTheme: 'light',
        }),
        (Story) => (
            <div className="min-h-screen bg-background text-foreground p-8 font-sans antialiased">
                <Story />
            </div>
        ),
    ],

    tags: ['autodocs'],
};

export default preview;
