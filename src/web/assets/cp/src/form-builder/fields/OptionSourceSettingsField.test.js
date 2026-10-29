import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';
import { IntegrationSourceChain } from './OptionSourceSettingsField';

const controls = vi.hoisted(() => {
    globalThis.Craft = { t: (_category, text) => {return text} };
    return { comboboxes: [] };
});
vi.mock('@verbb/plugin-kit-react/components', () => {return {
    Combobox: (props) => { controls.comboboxes.push(props); return null; },
    Icon: () => {return null},
    Option: () => {return null},
}});
vi.mock('@verbb/plugin-kit-react/forms', () => {return {}});
vi.mock('@verbb/plugin-kit-react/utils', () => {return {}});
vi.mock('@form-builder/hooks/useFormTools', () => {return {}});

// Required is the default for provider identity and dependent source parameters.
// A custom provider can explicitly declare a parameter optional.
describe('option source parameter clearing', () => {
    it('allows removing only optional selections and emits the empty value', () => {
        controls.comboboxes = [];
        const onChange = vi.fn();
        const steps = [undefined, true, false].map((required, index) => {return {
            key: String(index), label: 'Source', value: 'selected', required,
            options: [{ label: 'Selected', value: 'selected' }], onChange,
        }});
        renderToStaticMarkup(React.createElement(IntegrationSourceChain, { steps }));
        expect(controls.comboboxes.map(({ clearable }) => {return clearable})).toEqual([false, false, true]);
        controls.comboboxes[2].onPkChange({ detail: { value: '' } });
        expect(onChange).toHaveBeenCalledWith('');
    });
});
