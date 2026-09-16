import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { afterEach, expect, it, vi } from 'vitest';

vi.mock('@verbb/plugin-kit-react/components', () => {
    return { Icon: () => { return null; } };
});
vi.mock('@verbb/plugin-kit-react/utils', () => {
    return { cn: (...values) => { return values.filter(Boolean).join(' '); } };
});

import { FormieErrorsPane } from './FormieErrorsPane';

afterEach(() => { vi.unstubAllGlobals(); });

it('renders field labels and validation errors as text', () => {
    vi.stubGlobal('Craft', { t: (_category, message) => { return message; } });
    const html = renderToStaticMarkup(React.createElement(FormieErrorsPane, {
        errors: ['Contact > <img src=x onerror="alert(1)"> - Required', 'Name cannot be blank.'],
    }));
    expect(html).not.toContain('<img');
    expect(html).toContain('&lt;img src=x onerror=&quot;alert(1)&quot;&gt;');
    expect(html).toContain('Name cannot be blank.');
});
