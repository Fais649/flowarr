import { type ReactElement } from 'react';
import { render, type RenderOptions } from '@testing-library/react';

function customRender(ui: ReactElement, options?: Omit<RenderOptions, 'wrapper'>) {
    return render(ui, { ...options });
}

export { customRender as render };
