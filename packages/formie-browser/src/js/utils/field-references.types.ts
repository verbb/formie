export type FieldReferenceTransform = {
    id: string;
    params: Record<string, string>;
};

export type ParsedFieldReference = {
    raw: string;
    target: 'field' | '';
    key: string;
    selector: string;
    defaultValue: string;
    transforms: FieldReferenceTransform[];
    isToken: boolean;
    isValid: boolean;
};

export type FieldValueInput = HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement;

export type FieldValueRegistryEntry = {
    key: string;
    names: string[];
    inputs: FieldValueInput[];
};

export type FieldValueRegistry = Map<string, FieldValueRegistryEntry>;

export type ResolveFieldValueResult = {
    key: string;
    value: string | string[];
    found: boolean;
};
