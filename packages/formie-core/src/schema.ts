import type {
    ClientFieldDefinition,
    ClientFieldValueContract,
    ClientFormDefinition,
    ClientRowDefinition,
    ClientFormBootstrap,
    ClientSubmitResult,
    KnownClientFieldType,
} from './types.js';

const KNOWN_CLIENT_FIELD_TYPES = new Set<KnownClientFieldType>([
    'single-line-text',
    'multi-line-text',
    'number',
    'email',
    'phone',
    'dropdown',
    'radio',
    'checkboxes',
    'agree',
    'date',
    'name',
    'address',
    'repeater',
    'signature',
    'file',
]);

export function allFields(definition: ClientFormDefinition | ClientFormBootstrap['definition']): ClientFieldDefinition[] {
    return definition.pages.flatMap((page) => {
        return page.rows.flatMap((row) => row.fields);
    });
}

export function findFieldById(definition: ClientFormDefinition | ClientFormBootstrap['definition'], fieldId: string): ClientFieldDefinition | undefined {
    return allFields(definition).find((field) => field.id === fieldId);
}

export function findFieldByHandle(definition: ClientFormDefinition | ClientFormBootstrap['definition'], fieldHandle: string): ClientFieldDefinition | undefined {
    return allFields(definition).find((field) => field.handle === fieldHandle);
}

export function serializeFieldValues(definition: ClientFormDefinition, values: Record<string, unknown>): Record<string, unknown> {
    return Object.fromEntries(Object.entries(values).map(([fieldId, value]) => {
        const field = findFieldById(definition, fieldId);

        return [field?.handle ?? fieldId, value];
    }));
}

export function isKnownClientFieldType(fieldType: string): fieldType is KnownClientFieldType {
    return KNOWN_CLIENT_FIELD_TYPES.has(fieldType as KnownClientFieldType);
}

export function fieldValueContract(field: ClientFieldDefinition): ClientFieldValueContract {
    if (!field.client?.children) {
        throw new Error(`Field "${field.handle}" is missing client-rendered value metadata. Update Formie and its packages together.`);
    }

    return { structure: field.client.children.model, valueType: field.client.valueType };
}

export function fieldValueStructure(field: ClientFieldDefinition): ClientFieldValueContract['structure'] {
    return fieldValueContract(field).structure;
}

// Name, Address and Date have fixed parts, and a Group's parts work the same way in the client.
export function isCompositeField(field: ClientFieldDefinition): boolean {
    const structure = fieldValueStructure(field);

    return (structure === 'fixed-parent' || structure === 'container-parent') && compositePartDefinitions(field).length > 0;
}

const FIELD_KIND_RENDERERS: Record<string, string> = {
    text: 'single-line-text',
    textarea: 'multi-line-text',
    boolean: 'agree',
    file: 'file',
    select: 'dropdown',
    'radio-group': 'radio',
    'checkbox-group': 'checkboxes',
    signature: 'signature',
    table: 'table',
};

const SURVEY_RENDERERS: Record<string, string> = {
    likert: 'survey-likert',
    rank: 'survey-rank',
    rating: 'survey-rating',
    dropdown: 'dropdown',
    radio: 'radio',
    checkboxes: 'checkboxes',
    singleLineText: 'single-line-text',
    multiLineText: 'multi-line-text',
};

/**
 * The default control for a field. Fields without a built-in type, such as Recipients, Entries, Calculations or
 * Date and Address parts, use the control their field kind describes.
 */
export function resolveClientRendererType(field: ClientFieldDefinition): string {
    if (isKnownClientFieldType(field.type) || field.type === 'table' || field.type === 'section') {
        return field.type;
    }

    const fieldKind = typeof field.input.fieldKind === 'string' ? field.input.fieldKind : null;

    // Survey fields pick their control from the display type, as the server-rendered template does.
    if (field.type === 'survey') {
        const displayType = typeof field.input.displayType === 'string' ? field.input.displayType : '';

        return SURVEY_RENDERERS[displayType] ?? (fieldKind === 'radio-group' ? 'radio' : (fieldKind === 'checkbox-group' ? 'checkboxes' : field.type));
    }

    return (fieldKind && FIELD_KIND_RENDERERS[fieldKind]) || field.type;
}

export function isCalculationField(field: ClientFieldDefinition): boolean {
    return field.type === 'calculations';
}

// Summary fields show the visitor's saved answers, which the `formie:summary` browser module fetches from the server
// and draws inside this markup. The token from the latest accepted save says which answers it may show, and the
// module refreshes it from later submit results. Before a save there's nothing to show, as in server-rendered forms.
export function summaryFieldMarkup(field: ClientFieldDefinition, lastSubmitResult: ClientSubmitResult | null | undefined): string | null {
    if (field.type !== 'summary') {
        return null;
    }

    const token = lastSubmitResult?.summaryTokens?.[field.uid];
    const attribute = typeof token === 'string' && token ? ` data-formie-summary-token="${token.replace(/[&"<>]/g, (character) => `&#${character.charCodeAt(0)};`)}"` : '';

    return `<div data-formie-summary-container${attribute}><div data-formie-summary-blocks></div></div>`;
}

// Payment fields show their provider's markup, rendered by the server from the same template as server-rendered forms.
// The provider's browser module mounts into it and keeps its hidden inputs up to date.
export function paymentFieldMarkup(field: ClientFieldDefinition): string | null {
    return field.type === 'payment' && typeof field.input.html === 'string' ? field.input.html : null;
}

// The "Select all" checkbox of a Checkboxes field, when the field shows one.
export function checkboxToggle(field: ClientFieldDefinition): { position: 'top' | 'bottom'; label: string } | null {
    const position = field.input.toggleCheckbox;
    const label = typeof field.input.toggleCheckboxLabel === 'string' ? field.input.toggleCheckboxLabel : '';

    return (position === 'top' || position === 'bottom') && label ? { position, label } : null;
}

// "Select all" is checked once every option that can be chosen is.
export function allOptionsSelected(field: ClientFieldDefinition, selected: string[]): boolean {
    const enabled = fieldOptions(field).filter((option) => !option.disabled);

    return enabled.length > 0 && enabled.every((option) => selected.includes(option.value));
}

// Checking "Select all" chooses every enabled option, and unchecking it clears them. Disabled options keep their state.
export function toggleAllOptions(field: ClientFieldDefinition, selected: string[], checked: boolean): string[] {
    const options = fieldOptions(field);

    return options.filter((option) => (option.disabled ? selected.includes(option.value) : checked)).map((option) => option.value);
}

// Rich text Multi-line Text fields show the `formie:rich-text` editor, which writes its HTML into the field's textarea.
export function isRichTextField(field: ClientFieldDefinition): boolean {
    return field.input.useRichText === true;
}

// Date fields shown as a date picker use the `formie:date-picker` browser module on one input, which it fills with
// the picked date and time.
export function isDatePickerField(field: ClientFieldDefinition): boolean {
    return field.type === 'date' && field.input.displayType === 'datePicker' && field.input.collectMode !== 'range';
}

// The picker's answer is posted as `{ datetime }`, exactly as server-rendered date pickers post it, so both save the
// same answer. Values from other display types or older states are read from their date and time parts.
export function datePickerInputValue(value: unknown): string {
    const parts = value && typeof value === 'object' ? value as Record<string, unknown> : {};

    if (typeof parts.datetime === 'string') {
        return parts.datetime;
    }

    const date = typeof parts.date === 'string' ? parts.date : '';
    const time = typeof parts.time === 'string' ? parts.time : '';

    return [date, time].filter(Boolean).join(' ');
}

export function datePickerValue(inputValue: string): { datetime: string } {
    return { datetime: inputValue.trim() };
}

// Conditions compare dates by their parts, so a picked `datetime` is split into them.
export function datePickerParts(value: unknown): Record<string, string> {
    const match = datePickerInputValue(value).match(/^(\d{4})-(\d{2})-(\d{2})(?: (\d{2}):(\d{2})(?::(\d{2}))?)?$/);

    if (!match) {
        return {};
    }

    const [, year, month, day, hour, minute, second] = match;

    return Object.fromEntries(Object.entries({ year, month, day, hour, minute, second }).filter(([, part]) => part !== undefined)) as Record<string, string>;
}

export type SurveyLikertRow = {
    label: string;
    value: string;
};

// A Likert scale with multiple rows answers each row, as `{ row: column }`. Without rows it's one row answered with
// a single column value, like a Radio field.
export function surveyLikertRows(field: ClientFieldDefinition): SurveyLikertRow[] {
    const rows = Array.isArray(field.input.likertRows) ? field.input.likertRows as Array<Record<string, unknown>> : [];

    return rows.filter((row) => !!row && typeof row === 'object').map((row) => ({ label: String(row.label ?? ''), value: String(row.value ?? '') }));
}

// A Rank answer lists every option, in the order the visitor put them. Options missing from the answer keep their
// place at the end, so the list always matches what's shown.
export function surveyRankOrder(field: ClientFieldDefinition, value: unknown): string[] {
    const options = fieldOptions(field).map((option) => option.value);
    const ranked = (Array.isArray(value) ? value.map((item) => String(item)) : []).filter((item) => options.includes(item));

    return [...new Set(ranked), ...options.filter((option) => !ranked.includes(option))];
}

export function moveListItem<T>(items: T[], from: number, to: number): T[] {
    if (from === to || from < 0 || to < 0 || from >= items.length || to >= items.length) {
        return items;
    }

    const next = items.slice();
    const [moved] = next.splice(from, 1);
    next.splice(to, 0, moved);

    return next;
}

export type PhoneFieldValue = {
    number: string;
    country: string | null;
};

/**
 * The browser module dispatches this from a Phone field's country input when the visitor picks a country,
 * so client-rendered adapters can store it with the number.
 */
export const PHONE_COUNTRY_CHANGE_EVENT = 'formie:field:phone-country:change';

// A Phone answer is a number and a country, whether or not the field shows a country picker.
export function phoneFieldValue(value: unknown): PhoneFieldValue {
    if (typeof value === 'string' || typeof value === 'number') {
        return { number: String(value), country: null };
    }

    const parts = value && typeof value === 'object' ? value as Record<string, unknown> : {};
    const country = typeof parts.country === 'string' && parts.country !== '' ? parts.country : null;

    return { number: typeof parts.number === 'string' ? parts.number : '', country };
}

// The country picker is the `formie:phone-country` browser module, which attaches to the inputs the adapter renders.
export function isPhoneCountryEnabled(field: ClientFieldDefinition): boolean {
    return field.type === 'phone' && field.input.countryEnabled === true;
}

/**
 * Reads a Phone answer from the number and country inputs inside `container`. Picking a country fires `input` on
 * the number straight after the country change, before the framework re-renders, so a value from the last render
 * would put the previous country back.
 */
export function readPhoneFieldControls(container: Element | null | undefined, fallback: PhoneFieldValue): PhoneFieldValue {
    const number = container?.querySelector('input[data-formie-phone-input]');
    const country = container?.querySelector('input[data-formie-phone-country-input]');

    return {
        number: number instanceof HTMLInputElement ? number.value : fallback.number,
        country: country instanceof HTMLInputElement ? country.value || null : fallback.country,
    };
}

export type ClientFieldOption = {
    label: string;
    value: string;
    disabled: boolean;
};

export function fieldOptions(field: ClientFieldDefinition): ClientFieldOption[] {
    const options = Array.isArray(field.input.options) ? field.input.options as Array<Record<string, unknown>> : [];

    // Option group headings carry their label in `optgroup` and aren't choices.
    return options.filter((option) => !!option && typeof option === 'object' && !option.optgroup).map((option) => {
        const value = String(option.value ?? '');

        return { label: String(option.label ?? '') || value, value, disabled: option.disabled === true };
    });
}

// Element fields store a list of IDs even when only one can be chosen.
export function isArrayValuedField(field: ClientFieldDefinition): boolean {
    return field.input.multiple === true || field.client?.valueType?.kind === 'relationQuery';
}

/**
 * Options for a single-choice dropdown, with the placeholder as an empty first option. The server sends the
 * placeholder option for most dropdowns, but element fields only send the placeholder text.
 */
export function dropdownOptions(field: ClientFieldDefinition): ClientFieldOption[] {
    const options = fieldOptions(field);
    const placeholder = typeof field.input.placeholder === 'string' ? field.input.placeholder : '';

    if (field.input.multiple === true || !placeholder || options.some((option) => option.value === '')) {
        return options;
    }

    return [{ label: placeholder, value: '', disabled: false }, ...options];
}

// Dropdowns with **Searchable** on use the `formie:combobox` browser module (Tom Select), which hides the select and
// edits its options. Adapters render the select from this markup, which only depends on the definition, so the
// framework never manages the options. The module mirrors each change back to the select.
export function isSearchableDropdown(field: ClientFieldDefinition): boolean {
    return field.input.useSearchable === true;
}

export function searchableDropdownMarkup(field: ClientFieldDefinition, attributes: Record<string, string | boolean | null | undefined>): string {
    const escape = (text: string) => text.replace(/[&"<>]/g, (character) => `&#${character.charCodeAt(0)};`);
    const multiple = field.input.multiple === true;
    const initial = field.input.defaultValue;
    const selected = (Array.isArray(initial) ? initial : (initial == null || initial === '' ? [] : [initial])).map((item) => String(item));
    const options = multiple ? fieldOptions(field) : dropdownOptions(field);
    const attributeHtml = Object.entries({ ...attributes, 'data-formie-combobox-input': true, multiple })
        .filter(([, value]) => value !== undefined && value !== null && value !== false)
        .map(([name, value]) => (value === true ? name : `${name}="${escape(String(value))}"`))
        .join(' ');
    const optionHtml = options.map((option) => `<option value="${escape(option.value)}"${selected.includes(option.value) ? ' selected' : ''}${option.disabled ? ' disabled' : ''}>${escape(option.label)}</option>`).join('');

    return `<select ${attributeHtml}>${optionHtml}</select>`;
}

// The answer a searchable dropdown's select holds, in the shape the field stores.
export function searchableDropdownValue(field: ClientFieldDefinition, select: HTMLSelectElement): unknown {
    const values = Array.from(select.selectedOptions).map((option) => option.value).filter((value) => value !== '');

    if (field.input.multiple === true || isArrayValuedField(field)) {
        return values;
    }

    return values[0] ?? '';
}

export type ClientTableColumn = {
    id: string;
    handle: string;
    heading: string;
    type: string;
    options: ClientFieldOption[];
};

export function tableColumns(field: ClientFieldDefinition): ClientTableColumn[] {
    const columns = Array.isArray(field.input.columns) ? field.input.columns as Array<Record<string, unknown>> : [];

    return columns.filter((column) => !!column && typeof column === 'object').map((column) => {
        const id = String(column.id ?? '');
        const options = Array.isArray(column.options) ? column.options as Array<Record<string, unknown>> : [];

        return {
            id,
            handle: String(column.handle || id),
            heading: String(column.heading ?? ''),
            type: String(column.type ?? 'singleline'),
            options: options.map((option) => {
                const value = String(option.value ?? '');

                return { label: String(option.label ?? '') || value, value, disabled: false };
            }),
        };
    });
}

export function createTableRowValue(field: ClientFieldDefinition): Record<string, unknown> {
    return Object.fromEntries(tableColumns(field).filter((column) => column.type !== 'heading').map((column) => {
        if (column.type === 'checkbox') {
            return [column.handle, false];
        }

        // A select cell shows its first option, and the server-rendered table submits it.
        if ((column.type === 'select' || column.type === 'dropdown') && column.options.length > 0) {
            return [column.handle, column.options[0].value];
        }

        return [column.handle, ''];
    }));
}

export function isRepeatableField(field: ClientFieldDefinition): boolean {
    return fieldValueStructure(field) === 'repeatable-parent';
}

export function isFileField(field: ClientFieldDefinition): boolean {
    return field.type === 'file' || field.input.fieldKind === 'file';
}

export function isMultiValueField(field: ClientFieldDefinition): boolean {
    const rendererType = resolveClientRendererType(field);

    return isFileField(field)
        || rendererType === 'checkboxes'
        || (rendererType === 'dropdown' && isArrayValuedField(field))
        || (rendererType === 'radio' && isArrayValuedField(field));
}

export function isBooleanField(field: ClientFieldDefinition): boolean {
    return field.type === 'agree' || field.input.fieldKind === 'boolean';
}

export function isNumericField(field: ClientFieldDefinition): boolean {
    return field.type === 'number';
}

export function isEmailField(field: ClientFieldDefinition): boolean {
    return field.type === 'email';
}

export function compositePartDefinitions(field: ClientFieldDefinition): ClientFieldDefinition[] {
    const contract = field.input;

    if (Array.isArray(contract.parts)) {
        return contract.parts.filter((part): part is ClientFieldDefinition => {
            return !!part && typeof part === 'object' && 'handle' in part && 'type' in part;
        });
    }

    return [];
}

export function repeaterRowDefinitions(field: ClientFieldDefinition): ClientRowDefinition[] {
    const contract = field.input;
    const rowSchema = contract.rowSchema;

    if (!rowSchema || typeof rowSchema !== 'object' || !Array.isArray((rowSchema as { rows?: unknown }).rows)) {
        return [];
    }

    return (rowSchema as { rows: ClientRowDefinition[] }).rows;
}

export function repeaterFieldDefinitions(field: ClientFieldDefinition): ClientFieldDefinition[] {
    return repeaterRowDefinitions(field).flatMap((row) => row.fields);
}

export function defaultValueForField(field: ClientFieldDefinition): unknown {
    const contract = field.input;

    if (field.type === 'checkboxes') {
        const options = Array.isArray(contract.options) ? contract.options as Array<Record<string, unknown>> : [];

        return options.filter((option) => option.selected === true).map((option) => option.value ?? '');
    }

    const rendererType = resolveClientRendererType(field);

    if (rendererType === 'radio' || rendererType === 'dropdown') {
        const options = Array.isArray(contract.options) ? contract.options as Array<Record<string, unknown>> : [];

        if (rendererType === 'dropdown' && contract.multiple === true) {
            return options
                .filter((option) => option.selected === true)
                .map((option) => option.value ?? '');
        }

        // Element fields keep a list of IDs, even as a single-choice dropdown or radio.
        if (isArrayValuedField(field)) {
            const selected = options.filter((option) => option.selected === true).slice(0, 1).map((option) => String(option.value ?? ''));

            return selected.length > 0 ? selected : (Array.isArray(contract.defaultValue) ? contract.defaultValue : []);
        }

        const selectedOption = options.find((option) => option.selected === true);

        if (selectedOption) {
            return selectedOption.value ?? '';
        }

        if (rendererType === 'dropdown' && !isArrayValuedField(field) && (contract.defaultValue == null || contract.defaultValue === '')) {
            // A dropdown without an empty option shows its first option, and the server-rendered form submits it.
            const shown = dropdownOptions(field).find((option) => !option.disabled);

            if (shown && shown.value !== '' && !dropdownOptions(field).some((option) => option.value === '')) {
                return shown.value;
            }
        }
    }

    if (field.type === 'agree') {
        return contract.defaultValue ?? false;
    }

    if (isCompositeField(field)) {
        const sentValue = contract.defaultValue && typeof contract.defaultValue === 'object' ? contract.defaultValue as Record<string, unknown> : {};
        const partDefaults = Object.fromEntries(compositePartDefinitions(field).map((part) => [part.handle, defaultValueForField(part)]));

        // Parts the server sent no answer for start from their own defaults, as their controls show.
        return {
            ...partDefaults,
            ...Object.fromEntries(Object.entries(sentValue).filter(([, value]) => value != null)),
        };
    }

    if (rendererType === 'table') {
        const rows = Array.isArray(contract.defaultValue) ? contract.defaultValue : [];
        const minRows = Number(contract.minRows ?? 0) || 0;

        // Without default rows, the server-rendered table starts with its minimum number of empty rows. Cells a
        // default row leaves out start as a new row's would, and a select cell without one of its options sends
        // the first option, as the server-rendered table shows and submits it.
        const selectColumns = tableColumns(field).filter((column) => (column.type === 'select' || column.type === 'dropdown') && column.options.length > 0);

        return rows.length > 0
            ? rows.map((row) => {
                const cells: Record<string, unknown> = { ...createTableRowValue(field), ...(row && typeof row === 'object' ? row as Record<string, unknown> : {}) };

                selectColumns.forEach((column) => {
                    if (!column.options.some((option) => option.value === String(cells[column.handle] ?? ''))) {
                        cells[column.handle] = column.options[0].value;
                    }
                });

                return cells;
            })
            : Array.from({ length: minRows }, () => createTableRowValue(field));
    }

    if (isRepeatableField(field)) {
        const minRows = Number(contract.minRows ?? 0) || 0;
        // Prefilled rows arrive as the Repeater's default value.
        const sentRows = Array.isArray(contract.defaultValue) ? contract.defaultValue : [];
        const rows = sentRows
            .filter((row): row is Record<string, unknown> => !!row && typeof row === 'object' && !Array.isArray(row))
            .map((row) => ({ ...createRepeaterRowValue(field), ...row }));

        while (rows.length < minRows) {
            rows.push(createRepeaterRowValue(field));
        }

        return rows;
    }

    if (isFileField(field) || isMultiValueField(field)) {
        return [];
    }

    if (field.type === 'signature') {
        return contract.defaultValue ?? '';
    }

    return contract.defaultValue ?? '';
}

export type ClientDisplayContent =
    | { kind: 'html'; html: string }
    | { kind: 'heading'; level: 'h2' | 'h3' | 'h4' | 'h5' | 'h6'; text: string };

const HEADING_LEVELS = ['h2', 'h3', 'h4', 'h5', 'h6'] as const;

// Rich Text, HTML and Heading fields display content rather than collect a value.
// The server sends the same HTML its templates render.
export function displayFieldContent(field: ClientFieldDefinition): ClientDisplayContent | null {
    const contract = field.input;

    if (field.type === 'content' || field.type === 'html') {
        const html = field.type === 'content' ? contract.contentHtml : contract.htmlContent;

        return { kind: 'html', html: typeof html === 'string' ? html : '' };
    }

    if (field.type === 'heading') {
        const level = HEADING_LEVELS.find((candidate) => candidate === contract.headingSize) ?? 'h2';

        return { kind: 'heading', level, text: field.label ?? '' };
    }

    return null;
}

// A heading's label is its content, a Section is only a divider, and Rich Text and HTML labels are hidden unless the author shows them.
export function isFieldLabelHidden(field: ClientFieldDefinition): boolean {
    return field.type === 'heading' || field.type === 'section' || field.input.labelHidden === true;
}

export function createRepeaterRowValue(field: ClientFieldDefinition): Record<string, unknown> {
    return Object.fromEntries(repeaterFieldDefinitions(field).map((rowField) => {
        return [rowField.handle, defaultValueForField(rowField)];
    }));
}

export function fieldValueAsStrings(field: ClientFieldDefinition, value: unknown): string[] {
    if (field.type === 'checkboxes' || isFileField(field) || isMultiValueField(field)) {
        return Array.isArray(value) ? value.flatMap((item) => fieldValueAsStrings(field, item)) : [];
    }

    if (isRepeatableField(field)) {
        const rows = Array.isArray(value) ? value : [];
        const rowFields = repeaterFieldDefinitions(field);

        return rows.flatMap((row) => {
            if (!row || typeof row !== 'object') {
                return [];
            }

            const currentRow = row as Record<string, unknown>;

            return rowFields.flatMap((rowField) => {
                return fieldValueAsStrings(rowField, currentRow[rowField.handle]);
            });
        });
    }

    if (isCompositeField(field) && value && typeof value === 'object') {
        return Object.values(value as Record<string, unknown>).flatMap((item) => {
            return fieldValueAsStrings(field, item);
        });
    }

    if (value == null) {
        return [];
    }

    if (typeof value === 'boolean') {
        return value ? ['true'] : ['false'];
    }

    if (Array.isArray(value)) {
        return value.flatMap((item) => {
            return fieldValueAsStrings(field, item);
        });
    }

    return [String(value)];
}

type SerializableFileValue = {
    uploadUid?: string;
    attachToken?: string;
    assetId?: number;
    fileData?: string;
    filename?: string;
};

function isBlobLike(value: unknown): value is Blob {
    return typeof Blob !== 'undefined' && value instanceof Blob;
}

async function serializeFileEntries(value: unknown): Promise<SerializableFileValue[]> {
    const entries = Array.isArray(value) ? value : [];
    const output = await Promise.all(entries.map(async(entry) => {
        if (typeof entry === 'number') {
            return { assetId: entry };
        }

        if (entry && typeof entry === 'object' && typeof entry.uploadUid === 'string' && typeof entry.attachToken === 'string') {
            return { uploadUid: entry.uploadUid, attachToken: entry.attachToken };
        }

        if (entry && typeof entry === 'object' && 'assetId' in entry && typeof entry.assetId === 'number') {
            return {
                assetId: entry.assetId,
                filename: typeof entry.filename === 'string' ? entry.filename : undefined,
            };
        }

        if (entry && typeof entry === 'object' && 'fileData' in entry && typeof entry.fileData === 'string') {
            return {
                fileData: entry.fileData,
                filename: typeof entry.filename === 'string' ? entry.filename : undefined,
            };
        }

        if (isBlobLike(entry)) {
            throw new Error('Files must be staged before serialization. Use a Formie REST or GraphQL transport.');
        }

        return null;
    }));

    return output.filter((entry) => entry !== null);
}

async function serializeStructuredValue(fields: ClientFieldDefinition[], value: unknown): Promise<Record<string, unknown>> {
    const currentValue = value && typeof value === 'object' ? value as Record<string, unknown> : {};
    const output: Record<string, unknown> = {
        ...currentValue,
    };

    await Promise.all(fields.map(async(field) => {
        output[field.handle] = await serializeFieldValue(field, currentValue[field.handle]);
    }));

    return output;
}

async function serializeRepeaterValue(field: ClientFieldDefinition, value: unknown): Promise<unknown[]> {
    const rowFields = repeaterFieldDefinitions(field);

    if (rowFields.length === 0 || !Array.isArray(value)) {
        return [];
    }

    return Promise.all(value.map(async(rowValue) => {
        return serializeStructuredValue(rowFields, rowValue);
    }));
}

async function serializeFieldValue(field: ClientFieldDefinition, value: unknown): Promise<unknown> {
    if (isFileField(field)) {
        return serializeFileEntries(value);
    }

    if (isRepeatableField(field)) {
        return serializeRepeaterValue(field, value);
    }

    if (isCompositeField(field)) {
        return serializeStructuredValue(compositePartDefinitions(field), value);
    }

    return value;
}

export async function serializeTransportFieldValues(definition: ClientFormDefinition, values: Record<string, unknown>): Promise<Record<string, unknown>> {
    const entries = await Promise.all(Object.entries(values).map(async([fieldId, value]) => {
        const field = findFieldById(definition, fieldId);

        if (!field) {
            return [fieldId, value] as const;
        }

        return [field.handle, await serializeFieldValue(field, value)] as const;
    }));

    return Object.fromEntries(entries);
}
