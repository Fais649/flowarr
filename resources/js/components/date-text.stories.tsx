import type { Meta, StoryObj } from '@storybook/react-vite';
import { DateText } from './date-text';

const meta: Meta<typeof DateText> = {
    title: 'App/DateText',
    component: DateText,
    parameters: {
        layout: 'centered',
    },
    tags: ['autodocs'],
};

export default meta;
type Story = StoryObj<typeof DateText>;

export const Date: Story = {
    args: {
        value: '2026-07-26T10:00:00Z',
        format: 'date',
    },
};

export const DateTime: Story = {
    args: {
        value: '2026-07-26T10:00:00Z',
        format: 'datetime',
    },
};
