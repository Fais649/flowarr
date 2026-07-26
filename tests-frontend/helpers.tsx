import { render  } from '@testing-library/react';
import type {RenderOptions} from '@testing-library/react';
import type {ReactElement} from 'react';

function customRender(ui: ReactElement, options?: Omit<RenderOptions, 'wrapper'>) {
    return render(ui, { ...options });
}

export { customRender as render };
