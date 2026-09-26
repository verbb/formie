import type { ClientFormBootstrap } from './types';
import { assertBrowserModuleManifest } from './browser-modules';

export const CLIENT_FORM_CONTRACT_VERSION = 1;
export function assertClientFormBootstrap(value: unknown): asserts value is ClientFormBootstrap {
    const bootstrap = value as ClientFormBootstrap;
    if (!bootstrap || bootstrap.contractVersion !== CLIENT_FORM_CONTRACT_VERSION) {
        throw new Error('Unsupported client-rendered contractVersion. Update Formie and the client-rendered packages together.');
    }
    if (!bootstrap.definition || !Array.isArray(bootstrap.definition.pages) || !bootstrap.session) {
        throw new Error('Invalid client-rendered bootstrap: definition, pages and session are required.');
    }
    assertBrowserModuleManifest(bootstrap.definition.modules);
}
