/** The wire contract is shared by both markup products and CP editing. */
export const BROWSER_MODULE_CONTRACT_VERSION = 2;
export type BrowserSurface = 'server-rendered' | 'client-rendered' | 'cp-edit';
export type BrowserModuleKind = 'field' | 'captcha' | 'payment' | 'address' | 'core';
export type BrowserModuleTarget =
    | { type: 'form' }
    | { type: 'field'; uid: string }
    | { type: 'page'; id: string }
    | { type: 'action'; action: string }
    | { type: 'selector'; selector: string };
export type BrowserModuleEntry = {
    key: string;
    moduleId: string;
    kind: BrowserModuleKind;
    targets: BrowserModuleTarget[];
    config: Record<string, unknown>;
    required: boolean;
};
export type BrowserModuleManifest = { contractVersion: 2; surface: BrowserSurface; entries: BrowserModuleEntry[] };

export function assertBrowserModuleManifest(value: unknown): asserts value is BrowserModuleManifest {
    const manifest = value as BrowserModuleManifest;
    if (!manifest || manifest.contractVersion !== BROWSER_MODULE_CONTRACT_VERSION ||
        !['server-rendered', 'client-rendered', 'cp-edit'].includes(manifest.surface) || !Array.isArray(manifest.entries)) {
        throw new Error('Unsupported browser module contractVersion. Update Formie and its browser packages together.');
    }
    const keys = new Set<string>();
    for (const entry of manifest.entries) {
        if (!entry || typeof entry.key !== 'string' || !entry.key || keys.has(entry.key) ||
            typeof entry.moduleId !== 'string' || !/^[a-z][a-z0-9.-]*:[a-z][a-z0-9.-]*$/.test(entry.moduleId) ||
            'src' in entry || !Array.isArray(entry.targets) || entry.targets.length === 0 || typeof entry.required !== 'boolean' ||
            !entry.config || typeof entry.config !== 'object' || Array.isArray(entry.config) ||
            !['field', 'captcha', 'payment', 'address', 'core'].includes(entry.kind)) {
            throw new Error('Invalid browser module entry. Check the registered module ID, occurrence key, kind and targets.');
        }
        for (const target of entry.targets) {
            const keyCount = target && typeof target === 'object' ? Object.keys(target).length : 0;
            const valid = target && (
                (target.type === 'form' && keyCount === 1) ||
                (target.type === 'field' && keyCount === 2 && typeof target.uid === 'string' && target.uid !== '') ||
                (target.type === 'page' && keyCount === 2 && typeof target.id === 'string' && target.id !== '') ||
                (target.type === 'action' && keyCount === 2 && typeof target.action === 'string' && target.action !== '') ||
                (target.type === 'selector' && keyCount === 2 && typeof target.selector === 'string' && target.selector !== '')
            );
            if (!valid) {
                throw new Error('Invalid browser module target. Fields require a form-field instance UID.');
            }
        }
        keys.add(entry.key);
    }
}
