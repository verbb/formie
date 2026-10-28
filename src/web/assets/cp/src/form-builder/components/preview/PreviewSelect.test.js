import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { expect, it } from 'vitest';

import { PreviewSelect } from './PreviewSelect';

const render = (props) => {
    return renderToStaticMarkup(React.createElement(PreviewSelect, {
        options: [
            { label: 'Old prompt', value: '' },
            { label: 'Zero', value: '0', default: true },
            { label: 'One', value: '1' },
        ],
        placeholder: 'Choose a number',
        managePlaceholder: true,
        ...props,
    }));
};

it('keeps authored empty rows alongside the dedicated prompt and selected zero value', () => {
    const html = render();
    expect(html.match(/value=""/g)).toHaveLength(2);
    expect(html).toContain('Choose a number').toContain('Old prompt');
    expect(html).toContain('value="0" selected=""');
});

it('keeps empty source rows when the placeholder is disabled', () => {
    const html = render({ showPlaceholderOption: false });
    expect(html).toContain('Old prompt').not.toContain('Choose a number');
    expect(html.match(/value=""/g)).toHaveLength(1);
});

it('keeps an intentionally blank placeholder label', () => {
    expect(render({ placeholder: '' })).toContain('<option value=""></option>');
});

it('does not add an empty selectable item to a multi-select', () => {
    const html = render({ multiple: true });
    expect(html).toContain('Old prompt').not.toContain('Choose a number');
    expect(html.match(/value=""/g)).toHaveLength(1);
});

it('preserves a default inside an option group', () => {
    const html = render({ options: [{ optgroup: true, label: 'Numbers', options: [
        { label: 'Zero', value: '0' },
        { label: 'One', value: '1', default: true },
    ] }] });
    expect(html).toContain('value="1" selected=""');
});
