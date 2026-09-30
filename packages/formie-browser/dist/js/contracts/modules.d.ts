import type { FormAction } from '#contracts/common';
import type { BrowserModuleEntry, FormModuleTargetType, FormSubmitResult } from '#contracts/schema';
import type { BrowserModuleKind, BrowserSurface } from '@verbb/formie-core';
export type ModuleMatchContext = {
    root: Element;
    form: HTMLFormElement | null;
    target: Element;
    scope: FormModuleTargetType;
    surface: BrowserSurface;
    manifestItem: BrowserModuleEntry;
};
export type ModuleHookContext = {
    formId: string;
    root: Element;
    form: HTMLFormElement | null;
    target: Element;
    scope: FormModuleTargetType;
    state: Record<string, unknown>;
};
export type ModuleSetupContext = ModuleHookContext & {
    entryKey?: string;
    surface: BrowserSurface;
    options?: Record<string, unknown>;
    on: (eventName: string, callback: (payload: unknown) => void) => () => void;
    emit: (eventName: string, payload?: unknown) => Promise<void>;
};
export type ModuleRegistrationOptions = {
    replace?: boolean;
};
export type SubmitHookContext = {
    form: HTMLFormElement;
    action: FormAction;
    formData: FormData;
};
export type BeforeSubmitContext = SubmitHookContext & {
    abort: (reason?: string) => void;
    isAborted: () => boolean;
    abortReason: () => string | undefined;
};
export type AfterSubmitContext = SubmitHookContext;
export type BrowserModuleInstance = {
    assertReady?: () => void;
    key?: string;
    moduleId?: string;
    kind?: BrowserModuleKind;
    target?: Element;
    update?: (ctx: ModuleSetupContext) => void | Promise<void>;
    destroy: () => void | Promise<void>;
    beforeSubmit?: (ctx: BeforeSubmitContext) => void | Promise<void>;
    afterSubmit?: (ctx: AfterSubmitContext, result: FormSubmitResult) => void | Promise<void>;
};
export type BrowserModuleDefinition = {
    moduleId: string;
    version: 2;
    surfaces: BrowserSurface[];
    kind: BrowserModuleKind;
    match: (ctx: ModuleMatchContext) => boolean;
    setup: (ctx: ModuleSetupContext) => Promise<BrowserModuleInstance | void>;
};
export type BrowserModuleFailure = {
    key: string;
    moduleId: string;
    required: boolean;
    surface: BrowserSurface;
    code: 'MODULE_UNAVAILABLE';
    message: string;
};
export type BrowserModuleHydrationReport = {
    readonly instances: readonly BrowserModuleInstance[];
    readonly failures: readonly BrowserModuleFailure[];
};
//# sourceMappingURL=modules.d.ts.map