import { FormieClientFormElement } from './formie-client-form.js';
import { FormieFormElement } from './form-element.js';
import { FormieInternalSignature } from './signature-element.js';

export type { FormieFormElementOptions } from './form-element.js';
export { FormieFormElement } from './form-element.js';

export type { FormieRegionKey, FormieFieldControlElement, FormieFieldElement } from './types.js';
export { FORMIE_CONTROL_VALUE_EVENT } from './types.js';
export {
    assertValidCustomElementName,
    createFormieRegistry,
    FormieRegistry,
    getFormieRegistry,
} from './registry.js';
export type { FormieRenderHost, RenderViewContext } from './render-view.js';
export { renderErrorView, renderFormView, renderLoadingView } from './render-view.js';
export { FormieClientFormElement } from './formie-client-form.js';
export { FormieInternalSignature } from './signature-element.js';
export { isFieldDefinition, resolveFieldRendererType } from './field-utils.js';

let allRegistered = false;

/**
 * Registers all Formie custom elements: `formie-form` (server-rendered forms via formie-browser),
 * `formie-client-form` (definition-driven UI), and `formie-internal-signature`.
 * Safe to call more than once.
 */
export function registerFormieWebComponents(): void {
    if (allRegistered) {
        return;
    }

    allRegistered = true;

    if (!customElements.get('formie-form')) {
        customElements.define('formie-form', FormieFormElement);
    }

    if (!customElements.get('formie-internal-signature')) {
        customElements.define('formie-internal-signature', FormieInternalSignature);
    }

    if (!customElements.get('formie-client-form')) {
        customElements.define('formie-client-form', FormieClientFormElement);
    }
}

export { createFormieClient } from '@verbb/formie-browser';
export type {
    FormAction,
    FormEndpointPayload,
    FormEventUnsubscribe,
    FormMountOptions,
    FormieClient,
    FormieFormInstance,
    FormSubmitResult,
} from '@verbb/formie-browser';
