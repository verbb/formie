import { setFormBrowserRequestOptions } from '#utils/request-profile';
import type { ClientFormInstance } from '@verbb/formie-core';
import { hydrateFormieModules } from './hydrate-modules';
import { ModuleRegistry, clientRenderedModuleRegistry } from '#modules/registry';

// These capabilities are implemented by the shared core state engine or native
// framework controls. Explicit delegation preserves every declared occurrence.
const nativeCapabilities: Record<string, string> = {
    conditions: 'Core evaluates structured conditions and the adapter renders visibility.',
    repeater: 'The adapter owns row markup and core owns row values.',
    signature: 'The adapter owns its signature control and cleanup.',
    'file-upload': 'The shared transport stages selected files and submits attachment capabilities.',
    'upload-manager': 'Native file selection uses the shared staged upload transport.',
    'checkbox-radio': 'Framework controls own checked state.',
    'text-limit': 'Core validation enforces the structured minimum and maximum rules.',
    'date-picker': 'The adapter renders the structured date input contract.',
};

export async function mountClientRenderedModules(root: Element, instance: ClientFormInstance, registry = clientRenderedModuleRegistry) {
    for (const [capability, reason] of Object.entries(nativeCapabilities)) {
        if (registry.get(`formie:${capability}`)) continue;
        registry.register({
            moduleId: `formie:${capability}`,
            version: 1,
            surfaces: ['client-rendered'],
            kind: 'field',
            match: () => true,
            setup: async(context) => {
                await context.emit('formie:browser:module:delegated', { capability, reason, target: context.target });
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
        for (const [name, value] of Object.entries({ handle: state.definition.handle, ...(state.session.tokens.csrf ? { [state.session.tokens.csrf.name]: state.session.tokens.csrf.value } : {}) })) {
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
        void host.result({ ok: result.success, outcome: result.outcome, version: result.version, submissionUid: result.submissionUid, errors: result.errors, session: result.session, completion: result.completion, meta: result as unknown as Record<string, unknown> });
    });
    return { ...host, destroy: async() => { unsubscribe(); unsubscribeRequest(); await host.destroy(); } };
}
