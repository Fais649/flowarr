import type { Meta, StoryObj } from '@storybook/react-vite';
import { Input } from './input';

const meta: Meta<typeof Input> = {
    title: 'UI/Input',
    component: Input,
    parameters: {
        layout: 'centered',
    },
    tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof Input>;

export const Default: Story = {
    args: {
        placeholder: 'Enter text...',
    },
};

export const WithValue: Story = {
    args: {
        value: 'Some text',
        onChange: () => {},
    },
};

export const Email: Story = {
    args: {
        type: 'email',
        placeholder: 'email@example.com',
    },
};

export const Disabled: Story = {
    args: {
        placeholder: 'Disabled...',
        disabled: true,
    },
};
