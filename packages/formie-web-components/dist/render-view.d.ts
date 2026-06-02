import { type FrontendFormInstance, type FrontendFormState } from '@verbb/formie-core';
import { type TemplateResult } from 'lit';
import { type FormieRegistry } from './registry.js';
import type { LitElement } from 'lit';
export type FormieRenderHost = LitElement & {
    requestUpdate(name?: PropertyKey, oldValue?: unknown): void;
};
export type RenderViewContext = {
    registry: FormieRegistry;
    state: FrontendFormState;
    instance: FrontendFormInstance;
    host: FormieRenderHost;
    formClass: string;
};
export declare function renderFormView(ctx: RenderViewContext): TemplateResult;
export declare function renderLoadingView(message?: string): TemplateResult;
export declare function renderErrorView(message: string): TemplateResult;
//# sourceMappingURL=render-view.d.ts.map