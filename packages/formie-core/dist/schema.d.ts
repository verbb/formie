import type { ClientFieldDefinition, ClientFieldValueContract, ClientFormDefinition, ClientRowDefinition, ClientFormBootstrap, KnownClientFieldType } from './types.js';
export declare function allFields(definition: ClientFormDefinition | ClientFormBootstrap['definition']): ClientFieldDefinition[];
export declare function findFieldById(definition: ClientFormDefinition | ClientFormBootstrap['definition'], fieldId: string): ClientFieldDefinition | undefined;
export declare function findFieldByHandle(definition: ClientFormDefinition | ClientFormBootstrap['definition'], fieldHandle: string): ClientFieldDefinition | undefined;
export declare function serializeFieldValues(definition: ClientFormDefinition, values: Record<string, unknown>): Record<string, unknown>;
export declare function isKnownClientFieldType(fieldType: string): fieldType is KnownClientFieldType;
export declare function fieldValueContract(field: ClientFieldDefinition): ClientFieldValueContract;
export declare function fieldValueStructure(field: ClientFieldDefinition): ClientFieldValueContract['structure'];
export declare function isCompositeField(field: ClientFieldDefinition): boolean;
/**
 * The default control for a field. Fields without a built-in type, such as Recipients, Entries, Calculations or
 * Date and Address parts, use the control their field kind describes.
 */
export declare function resolveClientRendererType(field: ClientFieldDefinition): string;
export declare function isCalculationField(field: ClientFieldDefinition): boolean;
export declare function paymentFieldMarkup(field: ClientFieldDefinition): string | null;
export type PhoneFieldValue = {
    number: string;
    country: string | null;
};
/**
 * The browser module dispatches this from a Phone field's country input when the visitor picks a country,
 * so client-rendered adapters can store it with the number.
 */
export declare const PHONE_COUNTRY_CHANGE_EVENT = "formie:field:phone-country:change";
export declare function phoneFieldValue(value: unknown): PhoneFieldValue;
export declare function isPhoneCountryEnabled(field: ClientFieldDefinition): boolean;
/**
 * Reads a Phone answer from the number and country inputs inside `container`. Picking a country fires `input` on
 * the number straight after the country change, before the framework re-renders, so a value from the last render
 * would put the previous country back.
 */
export declare function readPhoneFieldControls(container: Element | null | undefined, fallback: PhoneFieldValue): PhoneFieldValue;
export type ClientFieldOption = {
    label: string;
    value: string;
    disabled: boolean;
};
export declare function fieldOptions(field: ClientFieldDefinition): ClientFieldOption[];
export declare function isArrayValuedField(field: ClientFieldDefinition): boolean;
/**
 * Options for a single-choice dropdown, with the placeholder as an empty first option. The server sends the
 * placeholder option for most dropdowns, but element fields only send the placeholder text.
 */
export declare function dropdownOptions(field: ClientFieldDefinition): ClientFieldOption[];
export type ClientTableColumn = {
    id: string;
    handle: string;
    heading: string;
    type: string;
    options: ClientFieldOption[];
};
export declare function tableColumns(field: ClientFieldDefinition): ClientTableColumn[];
export declare function createTableRowValue(field: ClientFieldDefinition): Record<string, unknown>;
export declare function isRepeatableField(field: ClientFieldDefinition): boolean;
export declare function isFileField(field: ClientFieldDefinition): boolean;
export declare function isMultiValueField(field: ClientFieldDefinition): boolean;
export declare function isBooleanField(field: ClientFieldDefinition): boolean;
export declare function isNumericField(field: ClientFieldDefinition): boolean;
export declare function isEmailField(field: ClientFieldDefinition): boolean;
export declare function compositePartDefinitions(field: ClientFieldDefinition): ClientFieldDefinition[];
export declare function repeaterRowDefinitions(field: ClientFieldDefinition): ClientRowDefinition[];
export declare function repeaterFieldDefinitions(field: ClientFieldDefinition): ClientFieldDefinition[];
export declare function defaultValueForField(field: ClientFieldDefinition): unknown;
export type ClientDisplayContent = {
    kind: 'html';
    html: string;
} | {
    kind: 'heading';
    level: 'h2' | 'h3' | 'h4' | 'h5' | 'h6';
    text: string;
};
export declare function displayFieldContent(field: ClientFieldDefinition): ClientDisplayContent | null;
export declare function isFieldLabelHidden(field: ClientFieldDefinition): boolean;
export declare function createRepeaterRowValue(field: ClientFieldDefinition): Record<string, unknown>;
export declare function fieldValueAsStrings(field: ClientFieldDefinition, value: unknown): string[];
export declare function serializeTransportFieldValues(definition: ClientFormDefinition, values: Record<string, unknown>): Promise<Record<string, unknown>>;
//# sourceMappingURL=schema.d.ts.map