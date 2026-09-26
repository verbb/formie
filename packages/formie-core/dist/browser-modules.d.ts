/** The wire contract is shared by both markup products and CP editing. */
export declare const BROWSER_MODULE_CONTRACT_VERSION = 1;
export type BrowserSurface = 'server-rendered' | 'client-rendered' | 'cp-edit';
export type BrowserModuleTarget = {
    [Kind in 'field' | 'form' | 'page' | 'button' | 'global']: {
        targetType: Kind;
        targetId: string;
    };
}['field' | 'form' | 'page' | 'button' | 'global'];
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
export type BrowserModuleManifest = {
    contractVersion: 1;
    entries: BrowserModuleEntry[];
};
export declare function assertBrowserModuleManifest(value: unknown): asserts value is BrowserModuleManifest;
//# sourceMappingURL=browser-modules.d.ts.map