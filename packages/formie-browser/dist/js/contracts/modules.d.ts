import type { FormAction, FormMode, SubmitStage } from '#contracts/common';
import type { BrowserModuleEntry, FormModuleTargetType, FormSubmitResult } from '#contracts/schema';
export type ModuleMatchContext = {
    root: Element;
    form: HTMLFormElement | null;
    target: Element;
    scope: FormModuleTargetType;
    mode: FormMode;
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
    surface?: import('@verbb/formie-core').BrowserSurface;
    options?: Record<string, unknown>;
    on: (eventName: string, callback: (payload: unknown) => void) => () => void;
    emit: (eventName: string, payload?: unknown) => Promise<void>;
};
export type ModuleRegistrationOptions = {
    replace?: boolean;
};
export type SubmitHookContext = {
    form: HTMLFormElement;
    stage: SubmitStage;
    action: FormAction;
    formData: FormData;
    abort: (reason?: string) => void;
    isAborted: () => boolean;
    abortReason: () => string | undefined;
};
export type BrowserModuleInstance = {
    assertReady?: () => void;
    key?: string;
    moduleId?: string;
    target?: Element;
    update?: (ctx: ModuleSetupContext) => void | Promise<void>;
    destroy: () => void | Promise<void>;
    onBeforeStage?: (ctx: SubmitHookContext) => void | Promise<void>;
    onAfterStage?: (ctx: SubmitHookContext, result?: FormSubmitResult) => void | Promise<void>;
};
export type BrowserModuleDefinition = {
    moduleId: string;
    version: 1;
    surfaces: import('@verbb/formie-core').BrowserSurface[];
    kind: 'field' | 'captcha' | 'payment' | 'address' | 'core';
    match: (ctx: ModuleMatchContext) => boolean;
    setup: (ctx: ModuleSetupContext) => Promise<BrowserModuleInstance | void>;
};
//# sourceMappingURL=modules.d.ts.map