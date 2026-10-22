import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { expect, it, vi } from 'vitest';

import { SubFieldPreviewControl } from './SubFieldPreviewControl';

vi.mock('@verbb/plugin-kit-react/utils', () => {
    return {
        cn: (...values) => { return values.filter(Boolean).join(' '); },
    };
});

vi.mock('@form-builder/hooks/useAppStore', () => {
    return {
        default: (selector) => { return selector({ getFieldTypeByType: () => { return null; } }); },
    };
});

const renderPreview = (field) => {
    return renderToStaticMarkup(React.createElement(SubFieldPreviewControl, { field }));
};

it('shows the country label for an ISO default in the fallback subfield preview', () => {
    expect(renderPreview({
        defaultValue: 'NL',
        options: [{ label: 'Nederland', value: 'NL' }],
    })).toContain('value="Nederland"');
});

it('preserves plain values and empty defaults in fallback previews', () => {
    expect(renderPreview({ defaultValue: 'Example' })).toContain('value="Example"');
    expect(renderPreview({ defaultValue: 0 })).toContain('value="0"');
    expect(renderPreview({ defaultValue: null })).toContain('value=""');
    expect(renderPreview({ defaultValue: { value: 'NL' } })).toContain('value=""');
});
