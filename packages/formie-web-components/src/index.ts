import { FormieCoreForm } from './formie-core-form.js';
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
export { FormieCoreForm } from './formie-core-form.js';
export { FormieInternalSignature } from './signature-element.js';
export { isFieldDefinition, resolveFieldRendererType } from './field-utils.js';

let allRegistered = false;

/**
 * Registers all Formie custom elements: `formie-form` (server-rendered forms via formie-browser),
 * `formie-core-form` (definition-driven UI), and `formie-internal-signature`.
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

    if (!customElements.get('formie-core-form')) {
        customElements.define('formie-core-form', FormieCoreForm);
    }
}

export { createFormieClient } from '@verbb/formie-browser';
export type {
    FormAction,
    FormDefinitionPayload,
    FormEndpointPayload,
    FormEventUnsubscribe,
    FormMountOptions,
    FormieClient,
    FormieFormInstance,
    FormSubmitResult,
} from '@verbb/formie-browser';
