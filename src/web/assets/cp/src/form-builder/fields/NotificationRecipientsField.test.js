import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';
import { NotificationRecipientsField } from './NotificationRecipientsField';

const controls = vi.hoisted(() => {return { table: null, value: [], setValue: vi.fn() }});

vi.mock('@verbb/plugin-kit-react/hooks', () => {return { useTranslation: () => {return (text) => {return text}} }});
vi.mock('@verbb/plugin-kit-react/forms', () => {return {
    FieldLayout: ({ children }) => {return children},
    useEngineField: () => {return { value: controls.value, setValue: controls.setValue, setTouched: vi.fn(), errors: [] }},
}});
vi.mock('@verbb/plugin-kit-react/components', () => {return {
    EditableTable: (props) => {
        controls.table = props;
        return null;
    },
}});
vi.mock('@form-builder/hooks/useAppStore', () => {return { default: (select) => {return select({})} }});
vi.mock('@form-builder/hooks/useFormTools', () => {return {
    useFormValues: () => {return {}},
    getFieldReferenceOptions: () => {return []},
}});
vi.mock('@form-builder/hooks/useVariableCategories', () => {return { useVariableCategories: () => {return {}} }});
vi.mock('@form-builder/fields/components/ConditionVariablePickerCell', () => {return {
    ConditionVariablePickerCell: () => {return null},
}});

const conditionOptions = [
    { label: 'Select an option', value: '' },
    { label: 'Equals', value: '=' },
    { label: 'Contains', value: 'contains' },
    { label: 'Is empty', value: 'empty' },
];

const renderRecipients = (fieldOptions = [], value = []) => {
    controls.value = value;
    controls.setValue.mockClear();
    renderToStaticMarkup(React.createElement(NotificationRecipientsField, {
        field: { name: 'recipients', conditionOptions, fieldOptions },
        form: {},
    }));
    return controls.table.modifyColumn;
};

describe('notification recipient conditions', () => {
    it.each(['current', 'legacy'])('displays and edits saved %s recipient rules', (format) => {
        const rows = [{ email: 'sales@example.test', field: '{field:department}', condition: '=', value: 'sales' }];
        renderRecipients([], format === 'legacy' ? { toRecipients: rows } : rows);
        expect(controls.table.rows).toEqual(rows);
        const updatedRows = [{ ...rows[0], value: 'support' }];
        controls.table.onChange(updatedRows);
        expect(controls.setValue).toHaveBeenCalledWith(updatedRows);
    });

    it('offers condition operators for a new row and a text field', () => {
        const modifyColumn = renderRecipients();
        expect(modifyColumn({}, 'condition').options).toEqual(conditionOptions);
        expect(modifyColumn({ field: '{field:firstName}' }, 'condition').options).toEqual(conditionOptions);
    });

    it('filters operators for the selected field and retains unsupported saved values', () => {
        const modifyColumn = renderRecipients([
            { value: '{field:choice}', conditionOperators: ['=', 'empty'] },
        ]);
        expect(modifyColumn({ field: '{field:choice}' }, 'condition').options).toEqual([
            conditionOptions[0], conditionOptions[1], conditionOptions[3],
        ]);
        expect(modifyColumn({ field: '{field:choice}', condition: 'contains' }, 'condition').options).toContainEqual({
            label: 'Unsupported condition', value: 'contains', disabled: true,
        });
    });

    it('preserves field-specific value controls for equality conditions', () => {
        const column = { type: 'select', options: [{ label: 'Sales', value: 'sales' }] };
        const modifyColumn = renderRecipients([{ value: '{field:choice}', column }]);
        expect(modifyColumn({ field: '{field:choice}', condition: '=' }, 'value')).toEqual(column);
        expect(modifyColumn({ field: '{field:choice}', condition: '!=' }, 'value')).toEqual(column);
        expect(modifyColumn({ field: '{field:choice}', condition: 'contains' }, 'value')).toBeUndefined();
    });
});
