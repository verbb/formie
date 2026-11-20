export { createClientFormInstance } from './form-instance.js';
export { CLIENT_FORM_EVENT_NAMES } from './event-names.js';
export {
    coerceCalculationVariables,
    evaluateCalculationExpression,
    formatCalculationValue,
    getCalculationFormula,
    getCalculationVariableEntries,
    readCalculationVariableValue,
} from './calculations.js';
export type {
    CalculationFormula,
    CalculationOptions,
    CalculationVariable,
    CalculationVariableEntry,
} from './calculations.js';
export { evaluateConditionDefinition, finalizeConditionEvaluation } from './conditions.js';
export { buildActionUrl, createRestClientTransport, loadClientFormBootstrap } from './rest.js';
export { createGraphqlClientTransport, loadGraphqlClientFormBootstrap } from './graphql.js';
export {
    allFields,
    compositePartDefinitions,
    createRepeaterRowValue,
    createTableRowValue,
    dropdownOptions,
    fieldOptions,
    isArrayValuedField,
    isCalculationField,
    isPhoneCountryEnabled,
    isSearchableDropdown,
    searchableDropdownMarkup,
    searchableDropdownValue,
    isDatePickerField,
    isDateRangePickerField,
    dateRangePickerMarkup,
    dateRangePickerValue,
    datePickerInputValue,
    datePickerValue,
    datePickerParts,
    isRichTextField,
    checkboxToggle,
    allOptionsSelected,
    toggleAllOptions,
    moveListItem,
    surveyLikertRows,
    surveyRankOrder,
    paymentFieldMarkup,
    uploadManagerMarkup,
    addressPartMarker,
    isAddressAutocompletePart,
    addressAutocompleteMarkup,
    readAddressParts,
    isAddressField,
    fieldContentPath,
    readUploadManagerEntries,
    summaryFieldMarkup,
    PHONE_COUNTRY_CHANGE_EVENT,
    phoneFieldValue,
    readPhoneFieldControls,
    resolveClientRendererType,
    tableColumns,
    defaultValueForField,
    displayFieldContent,
    fieldValueContract,
    fieldValueStructure,
    fieldValueAsStrings,
    findFieldById,
    findFieldByHandle,
    isBooleanField,
    isCompositeField,
    isEmailField,
    isFileField,
    isFieldLabelHidden,
    isKnownClientFieldType,
    isMultiValueField,
    isNumericField,
    isRepeatableField,
    repeaterFieldDefinitions,
    repeaterRowDefinitions,
    serializeFieldValues,
    serializeTransportFieldValues,
} from './schema.js';
export type { ClientFieldOption, ClientTableColumn, PhoneFieldValue, SurveyLikertRow, UploadManagerEntry } from './schema.js';
export { attachPointerReorder } from './reorder.js';
export { deriveCalculationValues, parseRowsExpression } from './client-calculations.js';
export { countGraphemes, getTextLimitMetrics, getWordCount, normalizeText } from './text.js';
export {
    getClientErrorAriaLive,
    getClientFieldErrorId,
    getClientFieldIds,
    getClientFieldLabelTarget,
    focusClientFormResult,
    getClientPageProgress,
    getClientString,
    showsClientPageTitle,
    isFieldLabelVisuallyHidden,
    joinIdReferences,
    VISUALLY_HIDDEN_CSS,
    VISUALLY_HIDDEN_STYLE,
} from './accessibility.js';
export type { ClientErrorAriaLive, ClientFieldElementIds, ClientFieldLabelTarget, ClientPageProgress } from './accessibility.js';

export type {
    ClientFieldDefinition,
    ClientFieldValueContract,
    ClientFieldValueStructure,
    ClientFieldType,
    ClientFieldValueType,
    ClientFormDefinition,
    ClientFormBootstrap,
    ClientFormSession,
    ClientStringKey,
    KnownClientFieldType,
    ClientPageDefinition,
    ClientRowDefinition,
    ClientFormEventName,
    ClientFormFieldState,
    ClientFormInstance,
    ClientFormPageState,
    ClientFormState,
    ClientSubmitAction,
    ClientSubmitResult,
    PaymentDecision,
    ClientTransport,
} from './types.js';
export { parseReference, serializeReference } from './references.js';
export type { ReferenceExpression } from './references.js';
export { resolveReference } from './references.js';
export type { ReferenceDefinition, ReferenceContext, ResolvedReference } from './references.js';

export * from './browser-modules.js';
export * from './contract.js';

export type { RequestProfile, BrowserRequestOptions } from './request-profile.js';

export { browserRequest, browserRequestHeaders } from './request-profile.js';

export { evaluateCondition, combineConditions, conditionNumber } from './conditions.js';
export type { BrowserConditionRule, BrowserConditionSettings, ConditionEvaluation, ConditionValueType } from './conditions.js';

export { validateBrowserValue, browserValueEmpty, registerBrowserValidationRule } from './validation.js';
export type { BrowserValidationRule, BrowserValidationContext } from './validation.js';

export { clientActionAllowed, evaluateClientCondition } from './condition-state.js';

export { projectElementConditionValues, selectConditionRows } from './condition-projections.js';
export type { ClientDisplayContent } from './schema.js';
