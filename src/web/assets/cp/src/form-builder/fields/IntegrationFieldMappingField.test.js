import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import {
    MappingValueControl,
    normalizeMappingValue,
} from './IntegrationFieldMappingField';

const controls = vi.hoisted(() => {
    globalThis.Craft = { t: (_category, text) => {return text} };
    return { picker: null, input: null, actions: [] };
});

vi.mock('@verbb/plugin-kit-react/utils', () => {return {
    cn: (...values) => {return values.filter(Boolean).join(' ')},
}});
vi.mock('@verbb/plugin-kit-react/forms', () => {return {
    FieldLayout: () => {return null},
    useEngineField: vi.fn(),
}});
vi.mock('@form-builder/hooks/useFormTools', () => {return {
    refreshIntegrationFormSettings: vi.fn(),
}});
vi.mock(
    '@form-builder/fields/components/FormBuilderVariablePickerControl',
    () => {return {
        FormBuilderVariablePickerControl: (props) => {
            controls.picker = props;
            return null;
        },
    }},
);
vi.mock('@form-builder/fields/variable-picker/VariablePickerInputCell', () => {return {
    VariablePickerInputCell: (props) => {
        controls.input = props;
        return null;
    },
}});
vi.mock('@form-builder/fields/components/VariablePickerActionsMenu', () => {return {
    VariablePickerActionsMenu: ({ children }) => {return children},
}});
vi.mock('@verbb/plugin-kit-react/components', () => {return {
    Button: () => {return null},
    Icon: () => {return null},
    Input: (props) => {
        controls.input = props;
        return null;
    },
    DropdownItem: (props) => {
        controls.actions.push(props);
        return null;
    },
}});

const options = [
    { label: 'Open', value: 2 },
    { label: 'Pending', value: 3 },
    { label: 'Zero', value: 0 },
    { label: 'Encoded', value: 'A/B + % {x}' },
];
const renderMapping = (storedValue, overrides = {}) => {
    controls.picker = null;
    controls.input = null;
    controls.actions = [];
    const onChange = vi.fn();
    renderToStaticMarkup(
        React.createElement(MappingValueControl, {
            mappingValue: normalizeMappingValue(storedValue),
            mappingKind: storedValue?.kind,
            onChange,
            variableCategories: {},
            baseVariableOptionIndex: {
                labelByValue: new Map(),
                optionByValue: new Map(),
            },
            baseFieldOptionValues: new Set(),
            integrationFieldOptions: options,
            variableTransformerRegistry: {},
            globalVariableCategoryLabels: {},
            variableCategoryOrder: [],
            ...overrides,
        }),
    );
    return onChange;
};

beforeEach(() => {
    vi.stubGlobal('Craft', { t: (_category, text) => {return text} });
    controls.picker = null;
    controls.input = null;
    controls.actions = [];
});

describe('integration provider option mappings', () => {
    it.each([
        2,
        '2',
        { value: 2 },
        { kind: 'text', value: '2' },
        { kind: 'literal', value: '2' },
    ])(
        'displays saved and migrated Open values in the picker: %j',
        (storedValue) => {
            renderMapping(storedValue);
            expect(controls.picker?.value).toBe('{providerOption:2}');
            expect(
                controls.picker.variableOptionIndex.labelByValue.get(
                    controls.picker.value,
                ),
            ).toBe('Open');
            expect(controls.input).toBeNull();
        },
    );

    it.each([0, { kind: 'literal', value: 0 }])(
        'preserves a zero-valued provider option: %j',
        (storedValue) => {
            renderMapping(storedValue);
            expect(controls.picker?.value).toBe('{providerOption:0}');
        },
    );

    it.each(['2', '0', 'A/B + % {x}'])(
        'stores a selected option literally and restores its picker identity: %s',
        (value) => {
            const onChange = renderMapping('');
            controls.picker.onChange(
                `{providerOption:${encodeURIComponent(value)}}`,
            );
            expect(onChange).toHaveBeenCalledWith({ kind: 'literal', value });
            renderMapping(
                JSON.parse(JSON.stringify(onChange.mock.calls[0][0])),
            );
            expect(controls.picker?.value).toBe(
                `{providerOption:${encodeURIComponent(value)}}`,
            );
        },
    );

    it('keeps existing provider tokens and field references selectable', () => {
        renderMapping('{providerOption:2}');
        expect(controls.picker?.value).toBe('{providerOption:2}');
        renderMapping({
            kind: 'reference',
            value: '{field:deleted;transform=upper|Fallback}',
        });
        expect(controls.picker?.value).toBe(
            '{field:deleted;transform=upper|Fallback}',
        );
    });

    it('preserves reference and empty selections', () => {
        const onChange = renderMapping('');
        controls.picker.onChange('{field:contact}');
        controls.picker.onChange('');
        expect(onChange.mock.calls).toEqual([
            [{ kind: 'reference', value: '{field:contact}' }],
            [{ kind: 'reference', value: '' }],
        ]);
    });

    it.each([
        { kind: 'literal', value: '99' },
        { kind: 'text', value: 'Ticket {field:contact}' },
    ])(
        'retains custom values without a matching provider option: %j',
        (storedValue) => {
            renderMapping(storedValue);
            expect(controls.input?.value).toBe(storedValue.value);
            expect(controls.picker).toBeNull();
        },
    );

    it('does not reinterpret an explicit reference as a literal option', () => {
        renderMapping(
            { kind: 'reference', value: '{field:contact}' },
            {
                integrationFieldOptions: [
                    { label: 'Literal token', value: '{field:contact}' },
                ],
            },
        );
        expect(controls.picker?.value).toBe('{field:contact}');
    });
});
