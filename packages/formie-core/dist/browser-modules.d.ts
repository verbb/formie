/** The wire contract is shared by both markup products and CP editing. */
export declare const BROWSER_MODULE_CONTRACT_VERSION = 2;
export type BrowserSurface = 'server-rendered' | 'client-rendered' | 'cp-edit';
export type BrowserModuleKind = 'field' | 'captcha' | 'payment' | 'address' | 'core';
export type BrowserModuleTarget = {
    type: 'form';
} | {
    type: 'field';
    uid: string;
} | {
    type: 'page';
    id: string;
} | {
    type: 'action';
    action: string;
} | {
    type: 'selector';
    selector: string;
};
export type BrowserModuleEntry = {
    key: string;
    moduleId: string;
    kind: BrowserModuleKind;
    targets: BrowserModuleTarget[];
    config: Record<string, unknown>;
    required: boolean;
};
export type BrowserModuleManifest = {
    contractVersion: 2;
    surface: BrowserSurface;
    entries: BrowserModuleEntry[];
};
export declare function assertBrowserModuleManifest(value: unknown): asserts value is BrowserModuleManifest;
//# sourceMappingURL=browser-modules.d.ts.map