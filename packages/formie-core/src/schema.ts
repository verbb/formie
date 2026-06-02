import type {
    FrontendFieldDefinition,
    FrontendFieldValueContract,
    FrontendFormDefinition,
    FrontendRowDefinition,
    FrontendFormEnvelope,
    KnownFrontendFieldType,
} from './types';

const KNOWN_FRONTEND_FIELD_TYPES = new Set<KnownFrontendFieldType>([
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

export function allFields(definition: FrontendFormDefinition | FrontendFormEnvelope['definition']): FrontendFieldDefinition[] {
    return definition.pages.flatMap((page) => {
        return page.rows.flatMap((row) => row.fields);
    });
}

export function findFieldById(definition: FrontendFormDefinition | FrontendFormEnvelope['definition'], fieldId: string): FrontendFieldDefinition | undefined {
    return allFields(definition).find((field) => field.id === fieldId);
}

export function findFieldByHandle(definition: FrontendFormDefinition | FrontendFormEnvelope['definition'], fieldHandle: string): FrontendFieldDefinition | undefined {
    return allFields(definition).find((field) => field.handle === fieldHandle);
}

export function serializeFieldValues(definition: FrontendFormDefinition, values: Record<string, unknown>): Record<string, unknown> {
    return Object.fromEntries(Object.entries(values).map(([fieldId, value]) => {
        const field = findFieldById(definition, fieldId);

        return [field?.handle ?? fieldId, value];
    }));
}

export function isKnownFrontendFieldType(fieldType: string): fieldType is KnownFrontendFieldType {
    return KNOWN_FRONTEND_FIELD_TYPES.has(fieldType as KnownFrontendFieldType);
}

export function fieldValueContract(field: FrontendFieldDefinition): FrontendFieldValueContract {
    if (!field.runtime) {
        throw new Error(`Field "${field.handle}" is missing field value metadata.`);
    }

    return field.runtime;
}

export function fieldValueStructure(field: FrontendFieldDefinition): FrontendFieldValueContract['structure'] {
    return fieldValueContract(field).structure;
}

export function isCompositeField(field: FrontendFieldDefinition): boolean {
    return fieldValueStructure(field) === 'fixed-parent' && compositePartDefinitions(field).length > 0;
}

export function isRepeatableField(field: FrontendFieldDefinition): boolean {
    return fieldValueStructure(field) === 'repeatable-parent';
}

export function isFileField(field: FrontendFieldDefinition): boolean {
    return field.type === 'file' || field.input.fieldKind === 'file';
}

export function isMultiValueField(field: FrontendFieldDefinition): boolean {
    const contract = field.input;

    return isFileField(field)
        || field.type === 'checkboxes'
        || (field.type === 'dropdown' && contract.multiple === true);
}

export function isBooleanField(field: FrontendFieldDefinition): boolean {
    return field.type === 'agree' || field.input.fieldKind === 'boolean';
}

export function isNumericField(field: FrontendFieldDefinition): boolean {
    return field.type === 'number';
}

export function isEmailField(field: FrontendFieldDefinition): boolean {
    return field.type === 'email';
}

export function compositePartDefinitions(field: FrontendFieldDefinition): FrontendFieldDefinition[] {
    const contract = field.input;

    if (Array.isArray(contract.parts)) {
        return contract.parts.filter((part): part is FrontendFieldDefinition => {
            return !!part && typeof part === 'object' && 'handle' in part && 'type' in part;
        });
    }

    return [];
}

export function repeaterRowDefinitions(field: FrontendFieldDefinition): FrontendRowDefinition[] {
    const contract = field.input;
    const rowSchema = contract.rowSchema;

    if (!rowSchema || typeof rowSchema !== 'object' || !Array.isArray((rowSchema as { rows?: unknown }).rows)) {
        return [];
    }

    return (rowSchema as { rows: FrontendRowDefinition[] }).rows;
}

export function repeaterFieldDefinitions(field: FrontendFieldDefinition): FrontendFieldDefinition[] {
    return repeaterRowDefinitions(field).flatMap((row) => row.fields);
}

export function defaultValueForField(field: FrontendFieldDefinition): unknown {
    const contract = field.input;

    if (field.type === 'checkboxes') {
        const options = Array.isArray(contract.options) ? contract.options as Array<Record<string, unknown>> : [];

        return options.filter((option) => option.selected === true).map((option) => option.value ?? '');
    }

    if (field.type === 'radio' || field.type === 'dropdown') {
        const options = Array.isArray(contract.options) ? contract.options as Array<Record<string, unknown>> : [];

        if (field.type === 'dropdown' && contract.multiple === true) {
            return options
                .filter((option) => option.selected === true)
                .map((option) => option.value ?? '');
        }

        const selectedOption = options.find((option) => option.selected === true);

        if (selectedOption) {
            return selectedOption.value ?? '';
        }
    }

    if (field.type === 'agree') {
        return contract.defaultValue ?? false;
    }

    if (isCompositeField(field)) {
        return contract.defaultValue && typeof contract.defaultValue === 'object'
            ? contract.defaultValue
            : {};
    }

    if (isRepeatableField(field)) {
        const minRows = Number(contract.minRows ?? 0) || 0;

        if (minRows <= 0) {
            return [];
        }

        return Array.from({ length: minRows }, () => {
            return createRepeaterRowValue(field);
        });
    }

    if (isFileField(field) || isMultiValueField(field)) {
        return [];
    }

    if (field.type === 'signature') {
        return contract.defaultValue ?? '';
    }

    return contract.defaultValue ?? '';
}

export function createRepeaterRowValue(field: FrontendFieldDefinition): Record<string, unknown> {
    return Object.fromEntries(repeaterFieldDefinitions(field).map((rowField) => {
        return [rowField.handle, defaultValueForField(rowField)];
    }));
}

export function fieldValueAsStrings(field: FrontendFieldDefinition, value: unknown): string[] {
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
    assetId?: number;
    fileData?: string;
    filename?: string;
};

function isBlobLike(value: unknown): value is Blob {
    return typeof Blob !== 'undefined' && value instanceof Blob;
}

async function blobToDataUrl(value: Blob): Promise<string> {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();

        reader.onerror = () => {
            reject(reader.error || new Error('Unable to read file.'));
        };
        reader.onload = () => {
            resolve(typeof reader.result === 'string' ? reader.result : '');
        };

        reader.readAsDataURL(value);
    });
}

async function serializeFileEntries(value: unknown): Promise<SerializableFileValue[]> {
    const entries = Array.isArray(value) ? value : [];
    const output = await Promise.all(entries.map(async(entry) => {
        if (typeof entry === 'number') {
            return { assetId: entry };
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
            return {
                fileData: await blobToDataUrl(entry),
                filename: 'name' in entry && typeof entry.name === 'string' ? entry.name : 'upload.bin',
            };
        }

        return null;
    }));

    return output.filter((entry) => entry !== null);
}

async function serializeStructuredValue(fields: FrontendFieldDefinition[], value: unknown): Promise<Record<string, unknown>> {
    const currentValue = value && typeof value === 'object' ? value as Record<string, unknown> : {};
    const output: Record<string, unknown> = {
        ...currentValue,
    };

    await Promise.all(fields.map(async(field) => {
        output[field.handle] = await serializeFieldValue(field, currentValue[field.handle]);
    }));

    return output;
}

async function serializeRepeaterValue(field: FrontendFieldDefinition, value: unknown): Promise<unknown[]> {
    const rowFields = repeaterFieldDefinitions(field);

    if (rowFields.length === 0 || !Array.isArray(value)) {
        return [];
    }

    return Promise.all(value.map(async(rowValue) => {
        return serializeStructuredValue(rowFields, rowValue);
    }));
}

async function serializeFieldValue(field: FrontendFieldDefinition, value: unknown): Promise<unknown> {
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

export async function serializeTransportFieldValues(definition: FrontendFormDefinition, values: Record<string, unknown>): Promise<Record<string, unknown>> {
    const entries = await Promise.all(Object.entries(values).map(async([fieldId, value]) => {
        const field = findFieldById(definition, fieldId);

        if (!field) {
            return [fieldId, value] as const;
        }

        return [field.handle, await serializeFieldValue(field, value)] as const;
    }));

    return Object.fromEntries(entries);
}
