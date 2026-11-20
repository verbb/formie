import { setFormBrowserRequestOptions } from '#utils/request-profile';
import type { BrowserModuleKind, ClientFormInstance } from '@verbb/formie-core';
import { hydrateFormieModules } from './hydrate-modules.js';
import { ModuleRegistry, clientRenderedModuleRegistry } from '#modules/registry';

// These modules are implemented by the shared core state engine or native
// framework controls. Explicit delegation preserves every declared occurrence.
const nativeModuleDelegations: Record<string, { kind: BrowserModuleKind; reason: string }> = {
    conditions: { kind: 'core', reason: 'Core evaluates structured conditions and the adapter renders visibility.' },
    repeater: { kind: 'field', reason: 'The adapter owns row markup and core owns row values.' },
    signature: { kind: 'field', reason: 'The adapter owns its signature control and cleanup.' },
    'file-upload': { kind: 'field', reason: 'The shared transport stages selected files and submits attachment capabilities.' },
    'checkbox-radio': { kind: 'field', reason: 'Framework controls own checked state.' },
    'text-limit': { kind: 'field', reason: 'Core validation enforces the structured minimum and maximum rules.' },
    calculations: { kind: 'field', reason: 'Core works out calculated answers from the form values.' },
    table: { kind: 'field', reason: 'The adapter owns table rows and core owns row values.' },
};

export async function mountClientRenderedModules(root: Element, instance: ClientFormInstance, registry = clientRenderedModuleRegistry) {
    for (const [moduleName, delegation] of Object.entries(nativeModuleDelegations)) {
        const moduleId = `formie:${moduleName}`;
        if (registry.get(moduleId)) continue;
        registry.register({
            moduleId,
            version: 2,
            surfaces: ['client-rendered'],
            kind: delegation.kind,
            match: () => true,
            setup: async(context) => {
                await context.emit('formie:browser:module:delegated', { moduleId, reason: delegation.reason, target: context.target });
                return { destroy: () => undefined };
            },
        });
    }
    const form = root instanceof HTMLFormElement ? root : root.querySelector('form');
    const syncRequest = () => {
        if (!form) return;
        const state = instance.getState();
        setFormBrowserRequestOptions(form, instance.getBrowserRequestOptions());
        form.action = state.definition.submission.endpoint;
        if (state.session.tokens.csrf) form.setAttribute('data-formie-csrf-param', state.session.tokens.csrf.name);
        // Modules that talk to Formie themselves, such as the Upload Manager, read the form's handle, CSRF token and
        // upload context from hidden inputs, as server-rendered forms provide them.
        const tokens = state.session.tokens as Record<string, unknown>;
        const continuation = (state.session.continuation ?? {}) as Record<string, unknown>;
        const context = Object.fromEntries(Object.entries({
            renderId: tokens.render,
            uploadCreateToken: tokens.uploadCreate,
            draftContext: continuation.draftContext,
            draftContextToken: continuation.draftContextToken,
        }).filter((entry): entry is [string, string] => typeof entry[1] === 'string' && entry[1] !== ''));
        for (const [name, value] of Object.entries({ handle: state.definition.handle, ...context, ...(state.session.tokens.csrf ? { [state.session.tokens.csrf.name]: state.session.tokens.csrf.value } : {}) })) {
            let input = form.querySelector<HTMLInputElement>(`input[type="hidden"][name="${CSS.escape(name)}"]`);
            if (!input) { input = document.createElement('input'); input.type = 'hidden'; input.name = name; form.append(input); }
            input.value = value;
        }
    };
    syncRequest();
    const unsubscribeRequest = instance.subscribe(syncRequest);
    const host = await hydrateFormieModules({ root, modules: instance.getState().definition.modules, surface: 'client-rendered', registry });
    instance.setBrowserModuleGuard(host.assertReady);
    instance.setBrowserModulePreparation(host.prepare);
    const unsubscribe = instance.on('formie:submit:result', (payload) => {
        const result = payload as import('@verbb/formie-core').ClientSubmitResult;
        const completion = result.completion;
        if (result.success && completion) {
            if (completion.behavior === 'redirect' && typeof completion.url === 'string') {
                if (completion.target === 'new-tab') window.open(completion.url, '_blank', 'noopener,noreferrer');
                else window.location.assign(completion.url);
            } else if (completion.behavior === 'reload') {
                window.location.reload();
            } else if (completion.behavior === 'reset') {
                instance.reset();
            }
        }
        void host.result({ ok: result.success, outcome: result.outcome, version: result.version, submissionUid: result.submissionUid, errors: result.errors, session: result.session, completion: result.completion, meta: result as unknown as Record<string, unknown> }).then(() => {
            // Payment providers continue from the server's decision as in server-rendered forms: their module confirms
            // the payment from an event, or the visitor goes to the provider's checkout. Modules finish their own
            // after-submit work first, as there, so it can't undo what the event handler sets.
            const paymentEvent = typeof result.payment?.action?.event === 'string' ? result.payment.action.event : '';
            if (form && paymentEvent) {
                form.dispatchEvent(new CustomEvent(paymentEvent, { bubbles: true, detail: { data: result.payment?.action?.payload } }));
            }
            const paymentRedirect = result.payment?.action?.type === 'redirect' && typeof result.payment.action.url === 'string' ? result.payment.action.url : null;
            const checkoutUrl = !result.success && !paymentEvent.startsWith('formie:payment:') ? (paymentRedirect ?? result.redirect?.url ?? null) : null;
            if (checkoutUrl) {
                if (result.redirect?.target === 'new-tab') window.open(checkoutUrl, '_blank', 'noopener,noreferrer');
                else window.location.assign(checkoutUrl);
            }
        });
    });
    return { ...host, get instances() { return host.instances; }, get failures() { return host.failures; }, destroy: async() => { unsubscribe(); unsubscribeRequest(); await host.destroy(); } };
}
