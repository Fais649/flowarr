import type { StorybookConfig } from '@storybook/react-vite';

const config: StorybookConfig = {
    stories: ['../resources/js/**/*.stories.tsx'],
    addons: [
        '@storybook/addon-vitest',
        '@storybook/addon-a11y',
        '@storybook/addon-docs',
        '@storybook/addon-mcp',
        '@storybook/addon-themes',
    ],
    framework: '@storybook/react-vite',
};
export default config;
