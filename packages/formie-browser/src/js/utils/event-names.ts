export const FORMIE_HTML_EVENT_NAMES = [
    'formie:mount:after',
    'formie:unmount:before',
    'formie:unmount:after',
    'formie:validator:ready',
    'formie:theme:applied',
    'formie:page:navigate',
    'formie:page:navigate:after',
    'formie:page:navigate:error',
    'formie:submit:before',
    'formie:submit:after',
    'formie:submit:final:before',
    'formie:submit:final:after',
    'formie:submit:result',
    'formie:client-event',
    'formie:refresh-tokens:after',
    'formie:refresh-tokens:refreshed',
] as const;

export function toDomEventName(eventName: string): string {
    return normalizeFormieEventName(eventName);
}

export function normalizeFormieEventName(eventName: string): string {
    return eventName;
}

export type ModuleLifecycleEventPhase = 'before-setup' | 'after-setup' | 'before-destroy' | 'after-destroy';

export function getFieldModuleEventName(moduleId: string, name: string): string {
    return `formie:field:${moduleId}:${name}`;
}

export function getValidatorEventName(name: string): string {
    return `formie:validator:${name}`;
}

export function getAddressProviderEventName(providerId: string, name: string): string {
    return `formie:address:${providerId}:${name}`;
}

export function getFileUploadEventName(name: string): string {
    return `formie:file-upload:${name}`;
}

export function getPaymentProviderActionEventName(providerId: string, action: string): string {
    return `formie:payment:${providerId}:${action}`;
}

export function getFormStateEventName(name: string): string {
    return `formie:state:${name}`;
}

export function getScopedModuleLifecycleEventName(moduleId: string, phase: ModuleLifecycleEventPhase): string {
    return `formie:module:${moduleId}:${phase}`;
}

export function getGlobalModuleLifecycleEventName(phase: ModuleLifecycleEventPhase): string {
    return `formie:module:${phase}`;
}
