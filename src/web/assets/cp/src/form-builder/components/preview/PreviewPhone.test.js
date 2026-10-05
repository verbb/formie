import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { afterEach, expect, it, vi } from 'vitest';

import { PreviewPhone } from './PreviewPhone';
import { PreviewSchemaProvider } from './PreviewSchemaContext';

afterEach(() => { vi.unstubAllGlobals(); });

const renderPhonePreview = (field) => {
    vi.stubGlobal('Craft', { t: (_category, message) => { return message; } });

    return renderToStaticMarkup(
        React.createElement(
            PreviewSchemaProvider,
            { value: { field, fieldType: {} } },
            React.createElement(PreviewPhone),
        ),
    );
};

it('renders an empty structured phone default without exposing its JSON', () => {
    const html = renderPhonePreview({
        countryEnabled: true,
        defaultValue: '{"number":"","country":null}',
    });

    expect(html)
        .toContain('value=""')
        .not.toContain('{&quot;number&quot;');
});

it('renders only the number from a structured phone default', () => {
    const html = renderPhonePreview({
        countryEnabled: false,
        defaultValue: {
            number: '+61 400 000 000',
            country: 'AU',
        },
    });

    expect(html)
        .toContain('value="+61 400 000 000"')
        .not.toContain('[object Object]');
});
