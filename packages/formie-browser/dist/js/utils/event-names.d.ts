export declare const FORMIE_HTML_EVENT_NAMES: readonly ["formie:mount:after", "formie:unmount:before", "formie:unmount:after", "formie:validator:ready", "formie:theme:applied", "formie:page:navigate", "formie:page:navigate:after", "formie:page:navigate:error", "formie:submit:before", "formie:submit:after", "formie:submit:final:before", "formie:submit:final:after", "formie:submit:result", "formie:client-event", "formie:refresh-tokens:after", "formie:refresh-tokens:refreshed"];
export declare function toDomEventName(eventName: string): string;
export declare function normalizeFormieEventName(eventName: string): string;
export type ModuleLifecycleEventPhase = 'before-setup' | 'after-setup' | 'before-destroy' | 'after-destroy';
export declare function getFieldModuleEventName(moduleId: string, name: string): string;
export declare function getValidatorEventName(name: string): string;
export declare function getAddressProviderEventName(providerId: string, name: string): string;
export declare function getFileUploadEventName(name: string): string;
export declare function getPaymentProviderActionEventName(providerId: string, action: string): string;
export declare function getFormStateEventName(name: string): string;
export declare function getScopedModuleLifecycleEventName(moduleId: string, phase: ModuleLifecycleEventPhase): string;
export declare function getGlobalModuleLifecycleEventName(phase: ModuleLifecycleEventPhase): string;
//# sourceMappingURL=event-names.d.ts.map