import { createElement } from 'react';
import { createRoot } from 'react-dom/client';
import { createApp, h } from 'vue';
import { FormieForm as ReactServerForm, FormieClientForm as ReactForm } from '../../packages/formie-react/src/index';
import { FormieForm as VueServerForm, FormieClientForm as VueForm } from '../../packages/formie-vue/src/index';
import { registerFormieWebComponents } from '../../packages/formie-web-components/src/index';

const adapter = new URLSearchParams(location.search).get('adapter') ?? 'react';
const query = new URLSearchParams(location.search);
const transport = query.get('transport') === 'graphql' ? 'graphql' : 'rest';
const serverRendered = query.get('product') === 'server';
const profile = query.get('profile') === 'cross-origin-public' ? 'cross-origin-public' : 'same-origin-browser';
const endpoint = query.get('endpoint') ?? location.origin;
const source = { profile, transport, endpoint: transport === 'graphql' ? `${endpoint}/actions/graphql/api` : endpoint, formHandle: new URLSearchParams(location.search).get('form') ?? 'browserContract' };
const hostQuery = Object.fromEntries(['note', 'utm_source'].flatMap(key => query.has(key) ? [[key, query.get(key)!]] : []));
Object.assign(source, { query: hostQuery });
const observations: unknown[] = [];
for (const name of ['formie:browser:module:mount', 'formie:browser:module:error', 'formie:browser:module:delegated']) document.addEventListener(name, (event) => observations.push({ name, ...(event as CustomEvent).detail, target: undefined }));
(globalThis as any).moduleObservations = observations;
const host = document.querySelector('#host')!;
let teardown: (() => void) | undefined;
function mount() {
    teardown?.();
    host.replaceChildren();
    if (adapter === 'react') {
        const root = createRoot(host);
        root.render(serverRendered ? createElement(ReactServerForm, { transport, profile, endpoint: source.endpoint, formHandle: source.formHandle }) : createElement(ReactForm, { source }));
        teardown = () => root.unmount();
    } else if (adapter === 'vue') {
        const app = createApp({ render: () => serverRendered ? h(VueServerForm, { transport, profile, endpoint: source.endpoint, formHandle: source.formHandle }) : h(VueForm, { source }) });
        app.mount(host);
        teardown = () => app.unmount();
    } else {
        registerFormieWebComponents();
        const element = document.createElement(serverRendered ? 'formie-form' : 'formie-client-form');
        element.setAttribute('endpoint', source.endpoint);
        element.setAttribute('transport', transport);
        element.setAttribute('request-profile', profile);
        element.setAttribute('form-handle', source.formHandle);
        if (!serverRendered) (element as any).query = hostQuery;
        host.append(element);
        teardown = () => element.remove();
    }
}
document.querySelector('#unmount')!.addEventListener('click', () => { teardown?.(); teardown = undefined; });
document.querySelector('#mount')!.addEventListener('click', mount);
mount();

// Synthetic provider harness: production module, isolated SDK and session response.
import { opayoModule } from '../../packages/formie-browser/src/js/modules/payments/opayo';
(globalThis as any).mountPaymentBoundary = async (form: HTMLFormElement) => {
    return opayoModule.setup({ formId: 'payment-boundary', form, root: form,
        target: form.querySelector('[data-formie-field-type="payment"]')!, scope: 'field', state: {},
        options: { handle: 'payment', checkoutMode: 'dropIn', sessionToken: 'scoped-session-token', sessionEndpoint: '/actions/formie/payment-sessions/initialize', useSandbox: true },
        on: () => () => {}, emit: async () => {},
    });
};

import { buildFieldValueRegistry, resolveFieldReferenceLive, resolveFieldReferenceFromFormData } from '../../packages/formie-browser/src/index';
import { resolveReference } from '../../packages/formie-core/src/index';
(globalThis as any).referenceBoundary = { buildFieldValueRegistry, resolveFieldReferenceLive, resolveFieldReferenceFromFormData, resolveReference };

import { hydrateFormieModules, ModuleRegistry, mountClientRenderedModules } from '../../packages/formie-browser/src/index';
(globalThis as any).browserModules = { hydrateFormieModules, ModuleRegistry, mountClientRenderedModules };

import { bindLegacyDomEventCompatibility, resolveLegacyCompatibilityOptions, createFormieClient } from '../../packages/formie-browser/src/index';
(globalThis as any).legacyModules = { bindLegacyDomEventCompatibility, resolveLegacyCompatibilityOptions, createFormieClient };

import { evaluateCondition } from '../../packages/formie-core/src/index';
(globalThis as any).conditionBoundary = { evaluateCondition };

import { submitForm } from '../../packages/formie-browser/src/js/transport/forms-api';
import { applySubmitResultState } from '../../packages/formie-browser/src/js/core/submit-result-state';
import { applySubmitResultUi } from '../../packages/formie-browser/src/js/core/submit-result-ui';
(globalThis as any).paymentResponseBoundary = { submitForm, applySubmitResultState, applySubmitResultUi };

import { parseConditionSettings } from '../../packages/formie-browser/src/js/modules/fields/conditions/config';
import { queryConditionInputs } from '../../packages/formie-browser/src/js/modules/fields/conditions/references';
import { evaluateConditionSettings } from '../../packages/formie-browser/src/js/modules/fields/conditions/evaluator';
import { applyConditionVisibility } from '../../packages/formie-browser/src/js/modules/fields/conditions/effects';
(globalThis as any).conditionDomBoundary = (root: Element, target: Element) => {
    const settings = parseConditionSettings(target)!;
    const result = evaluateConditionSettings(settings, (condition) => queryConditionInputs(root, target, condition), { root, from: target });
    applyConditionVisibility(target, result.shouldHide, settings.clearOnHide, { disabledOnly: ['enable', 'disable'].includes(settings.showRule) });
    return result;
};

import { conditionsModule } from '../../packages/formie-browser/src/js/modules/fields/conditions';
(globalThis as any).mountConditionsBoundary = async (root: HTMLElement) => conditionsModule.setup({ formId: 'condition-graph', form: null, root, target: root, scope: 'form', state: {}, options: {}, on: () => () => {}, emit: async () => {} });
