import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { FieldSelectField } from './FieldSelectField';

const controls = vi.hoisted(() => {
    globalThis.Craft = { t: (_category, text) => {return text} };
    return { options: [], combobox: null, setValue: vi.fn(), setTouched: vi.fn() };
});
vi.mock('@verbb/plugin-kit-react/components', () => {return {
    Combobox: (props) => { controls.combobox = props; return props.children; },
    Option: (props) => { controls.options.push(props); return null; },
    OptionGroup: ({ children }) => {return children},
}});
vi.mock('@verbb/plugin-kit-react/forms', () => {return {
    FieldLayout: ({ children }) => {return children},
    useEngineField: () => {return { value: '{field:other}', setValue: controls.setValue, setTouched: controls.setTouched, errors: [] }},
}});
vi.mock('@form-builder/hooks/useAppStore', () => {return {
    default: (selector) => {return selector({ getFieldTypeByType: () => {return {}} })},
}});
vi.mock('@form-builder/hooks/useFormTools', () => {return {
    useFormValues: () => {return {}},
    getFieldReferenceOptions: () => {return [{ label: 'Other field', value: '{field:other}', pageLabel: 'Page 1' }]},
}});

beforeEach(() => {
    controls.options = [];
    controls.setValue.mockClear();
    controls.setTouched.mockClear();
});

const renderField = (settings = {}) => {
    renderToStaticMarkup(React.createElement(FieldSelectField, { field: { name: 'matchField', ...settings }, form: {} }));
};

describe('optional field selections', () => {
    it('offers an empty choice after a field is selected and stores the clear', () => {
        renderField();
        expect(controls.options.map(({ value, children }) => {return { value, label: children }})).toEqual([
            { value: '', label: 'None' },
            { value: '{field:other}', label: 'Other field' },
        ]);
        controls.combobox.onPkChange({ detail: { value: '' } });
        expect(controls.setValue).toHaveBeenCalledWith('');
        expect(controls.setTouched).toHaveBeenCalledOnce();
    });

    it('preserves a meaningful empty-choice label', () => {
        renderField({ emptyOptionLabel: 'Always Opt-in' });
        expect(controls.options[0].children).toBe('Always Opt-in');
        expect(controls.options.filter(({ value }) => {return value === ''})).toHaveLength(1);
    });

    it('keeps required payment and mapping selections nonempty', () => {
        renderField({ required: true });
        expect(controls.options.map(({ value }) => {return value})).toEqual(['{field:other}']);
    });
});
