import { describe, expect, it } from 'vitest';
import { deriveCalculationValues } from './client-calculations';
import {
    createTableRowValue,
    defaultValueForField,
    dropdownOptions,
    fieldOptions,
    isCompositeField,
    isMultiValueField,
    isPhoneCountryEnabled,
    searchableDropdownMarkup,
    datePickerInputValue,
    datePickerValue,
    datePickerParts,
    isDatePickerField,
    checkboxToggle,
    allOptionsSelected,
    toggleAllOptions,
    moveListItem,
    surveyRankOrder,
    phoneFieldValue,
    resolveClientRendererType,
    tableColumns,
} from './schema';
import type { ClientFieldDefinition, ClientFormState } from './types';

const field = (type: string, input: Record<string, unknown> = {}, extra: Record<string, unknown> = {}): ClientFieldDefinition => ({
    id: type,
    handle: type,
    type,
    input,
    validation: [],
    moduleRefs: [],
    client: { children: { model: 'scalar', mode: 'none' }, valueType: { kind: 'string' } },
    ...extra,
} as unknown as ClientFieldDefinition);

const option = (value: unknown, label = String(value)) => ({ label, value, selected: false, disabled: false });

describe('client renderer types', () => {
    it('renders fields without a built-in type with the control their field kind describes', () => {
        expect(resolveClientRendererType(field('recipients', { fieldKind: 'select' }))).toBe('dropdown');
        expect(resolveClientRendererType(field('entries', { fieldKind: 'checkbox-group' }))).toBe('checkboxes');
        expect(resolveClientRendererType(field('recipients', { fieldKind: 'radio-group' }))).toBe('radio');
        expect(resolveClientRendererType(field('address-country', { fieldKind: 'select' }))).toBe('dropdown');
        expect(resolveClientRendererType(field('date-year-dropdown', { fieldKind: 'select' }))).toBe('dropdown');
        expect(resolveClientRendererType(field('calculations', { fieldKind: 'text' }))).toBe('single-line-text');
        expect(resolveClientRendererType(field('table', { fieldKind: 'table' }))).toBe('table');
        expect(resolveClientRendererType(field('section', { fieldKind: 'custom' }))).toBe('section');
        expect(resolveClientRendererType(field('radio', { fieldKind: 'select' }))).toBe('radio');
        expect(resolveClientRendererType(field('my-plugin-field', { fieldKind: 'custom' }))).toBe('my-plugin-field');
    });

    it('leaves Survey display types that need their own control unsupported', () => {
        expect(resolveClientRendererType(field('survey', { fieldKind: 'select' }))).toBe('survey');
        expect(resolveClientRendererType(field('survey', { fieldKind: 'radio-group' }))).toBe('radio');
        expect(resolveClientRendererType(field('survey', { fieldKind: 'checkbox-group' }))).toBe('checkboxes');
    });

    it('skips option group headings', () => {
        const recipients = field('recipients', { fieldKind: 'select', options: [{ label: '', value: '', optgroup: 'Teams' }, option('a')] });

        expect(fieldOptions(recipients).map((item) => item.value)).toEqual(['a']);
    });

    it('treats element fields as lists, whichever control they use', () => {
        const element = (fieldKind: string, input: Record<string, unknown> = {}) => field('entries', { fieldKind, options: [option('5'), option('6')], ...input }, {
            client: { children: { model: 'scalar' }, valueType: { kind: 'relationQuery' } },
        });

        expect(isMultiValueField(element('radio-group'))).toBe(true);
        expect(isMultiValueField(element('select'))).toBe(true);
        expect(defaultValueForField(element('radio-group'))).toEqual([]);
        expect(defaultValueForField(element('radio-group', { options: [option('5'), { ...option('6'), selected: true }] }))).toEqual(['6']);
        expect(defaultValueForField(element('select', { defaultValue: ['5'] }))).toEqual(['5']);
        expect(isMultiValueField(field('table', {}, { client: { children: { model: 'scalar' }, valueType: { kind: 'array' } } }))).toBe(false);
    });

    it('treats a Group like the other fields with parts', () => {
        const group = field('group', { parts: [field('single-line-text')] }, { client: { children: { model: 'container-parent' }, valueType: { kind: 'object' } } });

        expect(isCompositeField(group)).toBe(true);
        expect(defaultValueForField(group)).toEqual({ 'single-line-text': '' });
    });
});

describe('dropdown values', () => {
    it('starts on the option the dropdown shows when there is no empty option', () => {
        expect(defaultValueForField(field('dropdown', { fieldKind: 'select', options: [option('red'), option('blue')] }))).toBe('red');
        expect(defaultValueForField(field('date-year-dropdown', { fieldKind: 'select', options: [option(1926), option(1927)] }))).toBe('1926');
        expect(defaultValueForField(field('dropdown', { fieldKind: 'select', options: [{ ...option('red'), disabled: true }, option('blue')] }))).toBe('blue');
    });

    it('keeps an empty answer when the dropdown has a placeholder or a default', () => {
        expect(defaultValueForField(field('address-country', { fieldKind: 'select', placeholder: 'Select an option', options: [option('', 'Select an option'), option('AU')] }))).toBe('');
        expect(defaultValueForField(field('dropdown', { fieldKind: 'select', options: [option('red'), { ...option('blue'), selected: true }] }))).toBe('blue');
        expect(defaultValueForField(field('dropdown', { fieldKind: 'select', multiple: true, options: [option('red')] }))).toEqual([]);
        expect(defaultValueForField(field('entries', { fieldKind: 'select', placeholder: 'Select an entry', options: [option('5')], defaultValue: [] }, {
            client: { children: { model: 'scalar' }, valueType: { kind: 'relationQuery' } },
        }))).toEqual([]);
    });

    it('adds the placeholder option the server leaves out of element fields', () => {
        const entries = field('entries', { fieldKind: 'select', placeholder: 'Select an entry', options: [option('5', 'News')] });

        expect(dropdownOptions(entries).map((item) => item.value)).toEqual(['', '5']);
        expect(dropdownOptions(field('dropdown', { placeholder: 'Pick', options: [option('', 'Pick'), option('a')] })).map((item) => item.value)).toEqual(['', 'a']);
    });

    it('starts composite parts on the option their dropdown shows', () => {
        const date = field('date', {
            parts: [
                field('date-year-dropdown', { fieldKind: 'select', options: [option(2001), option(2002)] }, { handle: 'year' }),
                field('date-month-dropdown', { fieldKind: 'select', options: [option(1, 'January')] }, { handle: 'month' }),
            ],
            defaultValue: { month: '3' },
        }, { client: { children: { model: 'fixed-parent' }, valueType: { kind: 'object' } } });

        expect(defaultValueForField(date)).toEqual({ year: '2001', month: '3' });
    });
});

describe('table rows', () => {
    it('reads columns and creates empty rows keyed by column handle', () => {
        const table = field('table', {
            fieldKind: 'table',
            columns: [
                { id: 'col1', heading: 'Task', handle: 'task', type: 'singleline' },
                { id: 'col2', heading: 'Done', handle: 'done', type: 'checkbox' },
                { id: 'col3', heading: 'Notes', handle: '', type: 'heading' },
            ],
            defaultValue: [{ task: 'Plan', done: false }],
        }, { client: { children: { model: 'scalar' }, valueType: { kind: 'array' } } });

        expect(tableColumns(table).map((column) => column.handle)).toEqual(['task', 'done', 'col3']);
        expect(createTableRowValue(table)).toEqual({ task: '', done: false });
        expect(defaultValueForField(table)).toEqual([{ task: 'Plan', done: false }]);
    });

    it('starts select cells on their first option and adds the minimum rows', () => {
        const table = field('table', {
            fieldKind: 'table',
            minRows: 2,
            columns: [{ id: 'size', heading: 'Size', handle: 'size', type: 'select', options: [{ label: 'Small', value: 's' }, { label: 'Large', value: 'l' }] }],
            defaultValue: [],
        }, { client: { children: { model: 'scalar' }, valueType: { kind: 'array' } } });

        expect(defaultValueForField(table)).toEqual([{ size: 's' }, { size: 's' }]);
        expect(defaultValueForField({ ...table, input: { ...table.input, defaultValue: [{}] } })).toEqual([{ size: 's' }]);
        expect(defaultValueForField({ ...table, input: { ...table.input, defaultValue: [{ size: null }, { size: 'l' }] } })).toEqual([{ size: 's' }, { size: 'l' }]);
    });
});

describe('client calculations', () => {
    const state = (values: Record<string, unknown>, formatting = ''): ClientFormState => ({
        definition: {
            pages: [{ id: 'p', rows: [{ fields: [
                field('number', {}, { id: '1', handle: 'quantity' }),
                field('calculations', {}, { id: '2', handle: 'total', moduleRefs: ['calc'] }),
            ] }] }],
            modules: { entries: [{ key: 'calc', moduleId: 'formie:calculations', config: {
                formula: { expression: 'field_quantity * 2', variables: { field_quantity: { sourceKey: 'quantity' } } },
                formatting,
                decimals: 2,
            } }] },
        },
        values,
    } as unknown as ClientFormState);

    it('works out the answer from the form values', () => {
        expect(deriveCalculationValues(state({ 1: '7', 2: '' })).values[2]).toBe(14);
        expect(deriveCalculationValues(state({ 1: '1.5', 2: '' }, 'number')).values[2]).toBe('3.00');
    });

    it('returns the same state when nothing changes', () => {
        const current = state({ 1: '7', 2: 14 });

        expect(deriveCalculationValues(current)).toBe(current);
    });

    it('leaves a hidden Calculations field cleared', () => {
        const current = { ...state({ 1: '7', 2: null }), fieldStates: { 2: { hidden: true, disabled: false } } } as ClientFormState;

        expect(deriveCalculationValues(current).values[2]).toBeNull();
    });

    it('works out Calculations fields in a Group and in each Repeater row', () => {
        const calc = (handle: string, ref: string) => field('calculations', {}, { id: handle, handle, moduleRefs: [ref] });
        const nested = {
            definition: {
                pages: [{ id: 'p', rows: [{ fields: [
                    field('number', {}, { id: '1', handle: 'rate' }),
                    field('group', { parts: [field('number', {}, { id: '21', handle: 'hours' }), calc('groupTotal', 'group')] }, {
                        id: '2', handle: 'details', client: { children: { model: 'container-parent' }, valueType: { kind: 'object' } },
                    }),
                    field('repeater', { rowSchema: { rows: [{ fields: [field('number', {}, { id: '31', handle: 'qty' }), calc('rowTotal', 'row')] }] } }, {
                        id: '3', handle: 'items', client: { children: { model: 'repeatable-parent' }, valueType: { kind: 'array' } },
                    }),
                ] }] }],
                modules: { entries: [
                    { key: 'group', moduleId: 'formie:calculations', config: { formula: { expression: 'field_rate * field_details_hours', variables: { field_rate: { sourceKey: 'rate' }, field_details_hours: { sourceKey: 'details.hours' } } } } },
                    { key: 'row', moduleId: 'formie:calculations', config: { formula: { expression: 'field_rate * field_items_qty', variables: { field_rate: { sourceKey: 'rate' }, field_items_qty: { sourceKey: 'items.qty' } } } } },
                ] },
            },
            values: { 1: '3', 2: { hours: '2', groupTotal: '' }, 3: [{ qty: '4', rowTotal: '' }, { qty: '5', rowTotal: '' }, { qty: '6', rowTotal: '' }] },
            fieldStates: { '3.1.rowTotal': { hidden: true, disabled: false } },
        } as unknown as ClientFormState;
        const values = deriveCalculationValues(nested).values;

        expect(values[2]).toEqual({ hours: '2', groupTotal: 6 });
        // Each row reads its own Qty, and a hidden row field stays as it was.
        expect(values[3]).toEqual([{ qty: '4', rowTotal: 12 }, { qty: '5', rowTotal: '' }, { qty: '6', rowTotal: 18 }]);

        // A Group that conditions cleared isn't rebuilt with only its answer.
        expect(deriveCalculationValues({ ...nested, values: { ...nested.values, 2: null } } as unknown as ClientFormState).values[2]).toBeNull();
    });

    it('stops a formula that reads itself after a pass per Calculations field', () => {
        const looping = {
            definition: {
                pages: [{ id: 'p', rows: [{ fields: [
                    field('repeater', { rowSchema: { rows: [{ fields: [field('calculations', {}, { id: '31', handle: 'rowTotal', moduleRefs: ['row'] })] }] } }, {
                        id: '3', handle: 'items', client: { children: { model: 'repeatable-parent' }, valueType: { kind: 'array' } },
                    }),
                ] }] }],
                modules: { entries: [{ key: 'row', moduleId: 'formie:calculations', config: { formula: { expression: 'field_items_rowTotal + 1', variables: { field_items_rowTotal: { sourceKey: 'items.rowTotal' } } } } }] },
            },
            values: { 3: Array.from({ length: 50 }, () => ({ rowTotal: 0 })) },
        } as unknown as ClientFormState;

        expect((deriveCalculationValues(looping).values[3] as Array<{ rowTotal: number }>)[0].rowTotal).toBe(2);
    });

    it('picks Repeater rows by position, as the server-rendered module does', () => {
        const scoped = (variable: Record<string, unknown>) => ({
            definition: {
                pages: [{ id: 'p', rows: [{ fields: [
                    field('repeater', {}, { id: '1', handle: 'items' }),
                    field('calculations', {}, { id: '2', handle: 'total', moduleRefs: ['calc'] }),
                ] }] }],
                modules: { entries: [{ key: 'calc', moduleId: 'formie:calculations', config: {
                    formula: { expression: 'field_qty', variables: { field_qty: { sourceKey: 'items.qty', ...variable } } },
                } }] },
            },
            values: { 1: [{ qty: '2' }, { qty: '3' }, { qty: '5' }], 2: '' },
        } as unknown as ClientFormState);

        expect(deriveCalculationValues(scoped({ scope: 'count' })).values[2]).toBe(3);
        expect(deriveCalculationValues(scoped({ scope: 'first' })).values[2]).toBe(2);
        expect(deriveCalculationValues(scoped({ scope: 'last' })).values[2]).toBe(5);
        expect(deriveCalculationValues(scoped({ scope: 'index', index: 1 })).values[2]).toBe(3);
        expect(deriveCalculationValues(scoped({ scope: 'all' })).values[2]).toBe(10);
        expect(deriveCalculationValues(scoped({ scope: 'rows', rows: '1,3' })).values[2]).toBe(7);
        expect(deriveCalculationValues(scoped({ scope: 'rows', rows: 'even' })).values[2]).toBe(3);
    });
});

describe('phone values', () => {
    it('keeps the number and country together, whatever shape the value arrives in', () => {
        expect(phoneFieldValue({ number: '0412', country: 'AU' })).toEqual({ number: '0412', country: 'AU' });
        expect(phoneFieldValue({ number: '0412', country: '' })).toEqual({ number: '0412', country: null });
        expect(phoneFieldValue('0412')).toEqual({ number: '0412', country: null });
        expect(phoneFieldValue(null)).toEqual({ number: '', country: null });
    });

    it('only shows the country picker on Phone fields that enable it', () => {
        expect(isPhoneCountryEnabled(field('phone', { countryEnabled: true }))).toBe(true);
        expect(isPhoneCountryEnabled(field('phone', { countryEnabled: false }))).toBe(false);
        expect(isPhoneCountryEnabled(field('single-line-text', { countryEnabled: true }))).toBe(false);
    });
});

describe('survey display types', () => {
    const survey = (displayType: string, input: Record<string, unknown> = {}) => field('survey', { displayType, fieldKind: displayType === 'radio' ? 'radio-group' : (displayType === 'checkboxes' ? 'checkbox-group' : 'select'), options: [option('a'), option('b'), option('c')], ...input });

    it('picks the control from the display type', () => {
        expect(['likert', 'rank', 'rating', 'dropdown', 'radio', 'checkboxes', 'singleLineText', 'multiLineText'].map((type) => resolveClientRendererType(survey(type))))
            .toEqual(['survey-likert', 'survey-rank', 'survey-rating', 'dropdown', 'radio', 'checkboxes', 'single-line-text', 'multi-line-text']);
    });

    it('keeps every option in a Rank answer, in the visitor’s order', () => {
        expect(surveyRankOrder(survey('rank'), ['c', 'a'])).toEqual(['c', 'a', 'b']);
        expect(surveyRankOrder(survey('rank'), ['c', 'missing', 'c'])).toEqual(['c', 'a', 'b']);
        expect(surveyRankOrder(survey('rank'), null)).toEqual(['a', 'b', 'c']);
    });

    it('moves list items without changing the list it was given', () => {
        const items = ['a', 'b', 'c'];
        expect(moveListItem(items, 2, 0)).toEqual(['c', 'a', 'b']);
        expect(moveListItem(items, 0, 1)).toEqual(['b', 'a', 'c']);
        expect(moveListItem(items, 0, 5)).toBe(items);
        expect(items).toEqual(['a', 'b', 'c']);
    });
});

describe('select all', () => {
    const checkboxes = (input: Record<string, unknown> = {}) => field('checkboxes', { fieldKind: 'checkbox-group', options: [option('a'), option('b'), { ...option('c'), disabled: true }], ...input });

    it('only shows a toggle with a position and a label', () => {
        expect(checkboxToggle(checkboxes({ toggleCheckbox: 'top', toggleCheckboxLabel: 'All' }))).toEqual({ position: 'top', label: 'All' });
        expect(checkboxToggle(checkboxes({ toggleCheckbox: 'top' }))).toBeNull();
        expect(checkboxToggle(checkboxes())).toBeNull();
    });

    it('chooses and clears the enabled options and leaves disabled ones alone', () => {
        expect(toggleAllOptions(checkboxes(), [], true)).toEqual(['a', 'b']);
        expect(toggleAllOptions(checkboxes(), ['a', 'c'], false)).toEqual(['c']);
        expect(allOptionsSelected(checkboxes(), ['a', 'b'])).toBe(true);
        expect(allOptionsSelected(checkboxes(), ['a'])).toBe(false);
    });
});

describe('date pickers', () => {
    it('only treats single date pickers as one control', () => {
        expect(isDatePickerField(field('date', { displayType: 'datePicker', collectMode: 'single' }))).toBe(true);
        expect(isDatePickerField(field('date', { displayType: 'datePicker', collectMode: 'range' }))).toBe(false);
        expect(isDatePickerField(field('date', { displayType: 'calendar' }))).toBe(false);
    });

    it('posts the picked value as server-rendered date pickers do, and reads earlier part values', () => {
        expect(datePickerValue(' 2026-11-15 12:00:00 ')).toEqual({ datetime: '2026-11-15 12:00:00' });
        expect(datePickerInputValue({ datetime: '2026-11-15 12:00:00' })).toBe('2026-11-15 12:00:00');
        expect(datePickerInputValue({ date: '2026-11-15', time: '09:30' })).toBe('2026-11-15 09:30');
        expect(datePickerInputValue(null)).toBe('');
    });

    it('splits a picked value into the parts conditions compare', () => {
        expect(datePickerParts({ datetime: '2026-11-15 12:30:00' })).toEqual({ year: '2026', month: '11', day: '15', hour: '12', minute: '30', second: '00' });
        expect(datePickerParts({ datetime: '2026-11-15' })).toEqual({ year: '2026', month: '11', day: '15' });
        expect(datePickerParts({ datetime: '' })).toEqual({});
    });
});

describe('searchable dropdowns', () => {
    it('builds the select from the definition, with its default chosen and text escaped', () => {
        const single = field('dropdown', { useSearchable: true, placeholder: 'Pick', defaultValue: 'b', options: [option('a', 'A & "a"'), option('b')] });
        const multiple = field('dropdown', { useSearchable: true, multiple: true, defaultValue: ['a'], options: [option('a'), option('b')] });

        expect(searchableDropdownMarkup(single, { id: 'x', disabled: false, 'aria-required': undefined }))
            .toBe('<select id="x" data-formie-combobox-input><option value="">Pick</option><option value="a">A &#38; &#34;a&#34;</option><option value="b" selected>b</option></select>');
        expect(searchableDropdownMarkup(multiple, {})).toBe('<select data-formie-combobox-input multiple><option value="a" selected>a</option><option value="b">b</option></select>');
    });
});
