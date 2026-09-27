export type ConditionInput = HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;
export type ConditionSource = {
    raw: string;
    target: string;
    handle: string;
    selector: string;
    defaultValue: string;
    transformerId: string;
    transformerParams: Record<string, string>;
    isValid: boolean;
};
export type ConditionDefinition = {
    field: string;
    source?: ConditionSource | null;
    condition: string;
    valueType?: import('@verbb/formie-core').ConditionValueType;
    browserSafe?: boolean;
    value?: unknown;
};
export type ParsedConditionSettings = {
    showRule: 'show' | 'hide' | 'enable' | 'disable';
    conditionRule: 'all' | 'any';
    clearOnHide: boolean;
    isNested: boolean;
    conditions: ConditionDefinition[];
};
export type ConditionEntry = {
    node: Element;
    settings: ParsedConditionSettings;
    sourceInputs: ConditionInput[];
};
//# sourceMappingURL=types.d.ts.map