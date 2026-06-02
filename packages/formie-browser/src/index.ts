export { createFormieClient } from '#core/create-formie-client';
export { hydrateFormieModules } from '#core/hydrate-modules';
export { formie } from '#core/formie';
export { ModuleRegistry } from '#modules/registry';
export { FormieValidator } from '#validation/validator';
export { bindLegacyDomEventCompatibility } from '#compatibility/dom-adapter';
export { bindLegacyValidatorCompatibility } from '#compatibility/validator-adapter';
export {
    defineCaptchaModule,
    definePassiveCaptchaModule,
} from '#modules/captchas/api';
export { definePaymentModule } from '#modules/payments/api';
export { defineAddressModule } from '#modules/address/api';
// Re-export the package surface from one root entry so external consumers can
// mount forms, register modules, and share contracts without reaching into internals.
export {
    getFormieTranslations,
    mergeFormieTranslations,
    setFormieTranslations,
    t,
    translate,
} from '#utils/i18n';
export {
    createDebug,
    debugLog,
    debugWarn,
    isFormieDebugEnabled,
    setFormieDebugEnabled,
} from '#utils/debug';
export {
    LEGACY_FORMIE_DOM_EVENT_BRIDGES,
    LEGACY_FORMIE_VALIDATOR_EVENT_BRIDGES,
    resolveLegacyCompatibilityOptions,
} from '#compatibility/event-map';
export {
    FORMIE_HTML_EVENT_NAMES,
    getFieldModuleEventName,
    getGlobalModuleLifecycleEventName,
    getScopedModuleLifecycleEventName,
    normalizeFormieEventName,
    toDomEventName,
    type ModuleLifecycleEventPhase,
} from '#utils/event-names';
export {
    buildFieldValueRegistry,
    fieldKeyToInputName,
    inputNameToFieldKey,
    normalizeFieldKey,
    parseFieldReference,
    resolveFieldReferenceFromFormData,
    resolveFieldReferenceLive,
} from '#utils/field-references';
export type {
    AddressModuleContext,
    AddressModuleOptions,
    AddressProviderModule,
    AddressServices,
} from '#modules/address/api';
export type {
    CaptchaModuleContext,
    CaptchaModuleOptions,
    CaptchaProviderModule,
    CaptchaServices,
} from '#modules/captchas/api';
export type {
    PaymentModuleContext,
    PaymentModuleOptions,
    PaymentProviderModule,
    PaymentServices,
} from '#modules/payments/api';
export type {
    FormieClient,
    FormEventUnsubscribe,
    FormieFormInstance,
    FormMountOptions,
} from '#contracts/client';
export type { FormieModuleHydrator, FormieModuleHydratorOptions } from '#core/hydrate-modules';
export type { FormieApp, FormieElementTarget, FormieEvent, FormieOptions } from '#core/formie';
export type {
    LegacyBridgeDisposition,
    LegacyCompatibilityOptions,
    LegacyDomEventBridge,
    LegacyValidatorEventBridge,
    ResolvedLegacyCompatibilityOptions,
} from '#compatibility/event-map';
export type { FormAction, FormMode, FormTransport, SubmitStage } from '#contracts/common';
export type {
    FormieModuleDefinition,
    FormieModuleInstance,
    ModuleHookContext,
    ModuleMatchContext,
    ModuleRegistrationOptions,
    ModuleSetupContext,
    SubmitHookContext,
} from '#contracts/modules';
export type {
    FormRefreshTokensPayload,
    FormDefinitionField,
    FormDefinitionPage,
    FormDefinitionPayload,
    FormEndpointPayload,
    FormModuleManifest,
    FormModuleTarget,
    FormModuleTargetType,
    FormRedirect,
    FormSubmitResult,
} from '#contracts/schema';
export type { ThemeClassMap } from '#contracts/theme';
export type {
    ValidationConfig,
    ValidationContext,
    ValidationError,
    ValidationInput,
    ValidationRuleDefinition,
    ValidationRules,
    ValidationRuleValue,
} from '#validation/types';
export type { TranslationReplacements } from '#utils/i18n';
export type {
    FieldReferenceTransform,
    FieldValueRegistry,
    FieldValueRegistryEntry,
    ParsedFieldReference,
    ResolveFieldValueResult,
} from '#utils/field-references';
