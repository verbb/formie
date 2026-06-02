import { type FrontendFormInstance } from '@verbb/formie-core';
import { LitElement } from 'lit';
import { type FormieRegistry } from './registry.js';
export declare class FormieCoreForm extends LitElement {
    endpoint: string;
    formHandle: string;
    siteId?: number;
    transport: 'rest' | 'graphql';
    fetchCredentials: RequestCredentials;
    formClass: string;
    loadingMessage: string;
    /** Per-instance UI registry (defaults to {@link getFormieRegistry}). */
    registry: FormieRegistry | undefined;
    private loadError;
    private booting;
    private snapshot;
    private instance;
    private unsubscribers;
    private loadGeneration;
    createRenderRoot(): HTMLElement | DocumentFragment;
    disconnectedCallback(): void;
    connectedCallback(): void;
    protected willUpdate(changed: Map<PropertyKey, unknown>): void;
    /** Form instance after a successful load; `null` while loading or after teardown. */
    getFormieInstance(): FrontendFormInstance | null;
    /** Reload envelope + form instance from the server. */
    reload(): Promise<void>;
    private get resolvedRegistry();
    private teardown;
    private bootstrap;
    protected render(): import("lit-html").TemplateResult;
}
//# sourceMappingURL=formie-core-form.d.ts.map