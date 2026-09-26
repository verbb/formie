/** The wire contract is shared by both markup products and CP editing. */
export const BROWSER_MODULE_CONTRACT_VERSION = 1;
export type BrowserSurface = 'server-rendered' | 'client-rendered' | 'cp-edit';
export type BrowserModuleTarget = { [Kind in 'field' | 'form' | 'page' | 'button' | 'global']: { targetType: Kind; targetId: string } }['field' | 'form' | 'page' | 'button' | 'global'];
export type BrowserModuleEntry = {
    key: string;
    moduleId: string;
    type: string;
    capability: string;
    surfaces: BrowserSurface[];
    targets: BrowserModuleTarget[];
    config: Record<string, unknown>;
    required: boolean;
};
export type BrowserModuleManifest = { contractVersion: 1; entries: BrowserModuleEntry[] };

export function assertBrowserModuleManifest(value: unknown): asserts value is BrowserModuleManifest {
    const manifest = value as BrowserModuleManifest;
    if (!manifest || manifest.contractVersion !== BROWSER_MODULE_CONTRACT_VERSION || !Array.isArray(manifest.entries)) {
        throw new Error('Unsupported browser module contractVersion. Update Formie and its browser packages together.');
    }
    const keys = new Set<string>();
    for (const entry of manifest.entries) {
        if (!entry || typeof entry.key !== 'string' || !entry.key || keys.has(entry.key) ||
            typeof entry.moduleId !== 'string' || !/^[a-z][a-z0-9.-]*:[a-z][a-z0-9.-]*$/.test(entry.moduleId) ||
            'src' in entry || !Array.isArray(entry.surfaces) || !Array.isArray(entry.targets) || typeof entry.required !== 'boolean' ||
            entry.surfaces.some((surface) => !['server-rendered', 'client-rendered', 'cp-edit'].includes(surface)) ||
            !entry.config || typeof entry.config !== 'object' || (Array.isArray(entry.config) && entry.config.length > 0) || typeof entry.type !== 'string' || typeof entry.capability !== 'string') {
            throw new Error('Invalid browser module entry. Check the registered module ID, occurrence key and surfaces.');
        }
        for (const target of entry.targets) {
            if (!target || !['field', 'form', 'page', 'button', 'global'].includes(target.targetType) || typeof target.targetId !== 'string') {
                throw new Error('Invalid browser module target. Fields require a form-field instance UID.');
            }
        }
        keys.add(entry.key);
    }
}
