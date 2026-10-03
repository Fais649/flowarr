import { fireEvent, render, screen } from '@testing-library/react';
import { useState } from 'react';
import { expect, it } from 'vitest';
import type { OriginalHandling } from '@/components/original-handling-fields';
import OriginalHandlingFields from '@/components/original-handling-fields';

it('allows selecting replacement mode and entering an overnight window', () => {
    function Form() {
        const [value, setValue] = useState<OriginalHandling>({
            original_handling: 'keep',
            replacement_start: '22:00',
            replacement_end: '06:00',
        });

        return <OriginalHandlingFields value={value} onChange={setValue} />;
    }
    render(<Form />);
    expect(screen.queryByLabelText('From')).not.toBeInTheDocument();
    fireEvent.change(screen.getByLabelText('Handling'), {
        target: { value: 'window' },
    });
    expect(screen.getByLabelText('From')).toHaveValue('22:00');
    expect(screen.getByLabelText('Until')).toHaveValue('06:00');
    fireEvent.change(screen.getByLabelText('Handling'), {
        target: { value: 'idle' },
    });
    expect(screen.queryByLabelText('From')).not.toBeInTheDocument();
});

it('shows invalid window errors', () => {
    render(
        <OriginalHandlingFields
            value={{
                original_handling: 'window',
                replacement_start: '',
                replacement_end: '',
            }}
            onChange={() => {}}
            errors={{ replacement_start: 'Choose a start time.' }}
        />,
    );
    expect(screen.getByText('Choose a start time.')).toBeInTheDocument();
});
