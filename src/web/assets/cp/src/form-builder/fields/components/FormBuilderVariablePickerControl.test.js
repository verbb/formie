import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';

import { FormBuilderVariablePickerControl } from './FormBuilderVariablePickerControl';

const { passThrough } = vi.hoisted(() => {
    return { passThrough: ({ children }) => {return children} };
});

vi.mock('@verbb/plugin-kit-react/hooks', () => {return { useTranslation: () => {return (text) => {return text}} }});
vi.mock('@verbb/plugin-kit-react/utils', () => {return { cn: (...values) => {return values.filter(Boolean).join(' ')} }});
vi.mock('@verbb/plugin-kit-react/components', () => {return {
    Button: passThrough,
    Popover: passThrough,
    Icon: () => {return null},
    Input: () => {return null},
    DropdownItem: () => {return null},
}});
vi.mock('@form-builder/fields/variable-picker/useVariablePicker', () => {return {
    useVariablePicker: () => {return { groups: [], page: null, search: '', setSearch: () => {}, options: [], handleSelect: () => {}, handleBack: () => {} }},
}});
vi.mock('@form-builder/fields/variable-picker/VariableCommandList', () => {return { VariableCommandList: () => {return null} }});
vi.mock('@form-builder/fields/variable-picker/VariableTransformControls', () => {return { VariableTransformControls: () => {return null} }});
vi.mock('./VariablePickerActionsMenu', () => {return { VariablePickerActionsMenu: passThrough }});
vi.mock('./RepeaterRowTargetingControls', () => {return { RepeaterRowTargetingControls: () => {return null} }});

const variableCategories = {
    form: [{ label: 'Form Name', value: '{form:name}', types: ['text'] }],
    fields: [{ label: 'Notes', value: '{field:notes}', types: ['text'] }],
};

const render = (value) => {
    const actions = {};
    const html = renderToStaticMarkup(React.createElement(FormBuilderVariablePickerControl, {
        value,
        onChange: () => {},
        variableCategories,
        noneOptionLabel: 'Don’t Include',
        renderActionItems: ({ canShowSettings }) => {
            actions.canShowSettings = canShowSettings;
            return null;
        },
    }));
    const label = html.match(/<span class="truncate flex-1 text-left">([^<]*)<\/span>/)?.[1];
    return { label, html, ...actions };
};

describe('picker trigger for a variable with a transform', () => {
    it.each([
        ['{form:name;transform=lower}', 'Form Name'],
        ['{field:notes;transform=lower}', 'Notes'],
    ])('names %s and keeps Configure Value available', (value, label) => {
        const result = render(value);
        expect(result.label).toBe(label);
        expect(result.html).toContain('>lower</span>');
        expect(result.canShowSettings).toBe(true);
    });

    it('shows an empty value as unmapped', () => {
        const result = render('');
        expect(result.label).toBe('Don’t Include');
        expect(result.canShowSettings).toBe(false);
    });
});
