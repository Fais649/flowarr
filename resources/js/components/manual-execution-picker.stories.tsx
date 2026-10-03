import type { Meta, StoryObj } from '@storybook/react-vite';
import ManualExecutionPicker from './manual-execution-picker';
const meta = {
    title: 'Components/ManualExecutionPicker',
    component: ManualExecutionPicker,
    beforeEach: () => {
        const previous = globalThis.fetch;
        globalThis.fetch = async (input, options) => {
            const url = new URL(String(input), window.location.origin);

            if (!url.pathname.startsWith('/executions/files/')) {
                return previous(input, options);
            }

            return new Response(
                JSON.stringify({
                    path: '/media/TV',
                    parent: null,
                    entries: [
                        {
                            name: 'Season 1',
                            path: '/media/TV/Season 1',
                            directory: true,
                        },
                        {
                            name: 'Episode 1.mkv',
                            path: '/media/TV/Episode 1.mkv',
                            directory: false,
                        },
                    ],
                }),
                { headers: { 'Content-Type': 'application/json' } },
            );
        };

        return () => {
            globalThis.fetch = previous;
        };
    },
} satisfies Meta<typeof ManualExecutionPicker>;
export default meta;
type Story = StoryObj<typeof meta>;
export const Default: Story = {
    args: {
        libraries: [
            {
                id: 1,
                base_path: '/media/TV',
                workers: [{ job_type: 'transcode_media', enabled: true }],
            },
        ],
    },
};
