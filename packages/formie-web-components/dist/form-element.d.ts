import type { FormAction, FormEndpointPayload, FormMountOptions, FormieFormInstance, FormSubmitResult } from '@verbb/formie-browser';
export type FormieFormElementOptions = {
    baseUrl?: string;
} & Partial<FormMountOptions>;
export declare class FormieFormElement extends HTMLElement {
    static get observedAttributes(): string[];
    private client;
    private mountRoot;
    private mountedInstance;
    private optionState;
    private mountScheduled;
    private eventUnsubs;
    constructor();
    /** Lazily create client + mount container (not in the constructor). */
    private ensureInitialized;
    connectedCallback(): void;
    disconnectedCallback(): void;
    attributeChangedCallback(name: string, oldValue: string | null, newValue: string | null): void;
    get baseUrl(): string | undefined;
    set baseUrl(value: string | undefined);
    get transport(): FormMountOptions["transport"] | undefined;
    set transport(value: FormMountOptions['transport'] | undefined);
    get theme(): FormMountOptions["theme"] | undefined;
    set theme(value: FormMountOptions['theme'] | undefined);
    get themeConfig(): FormMountOptions["themeConfig"] | undefined;
    set themeConfig(value: FormMountOptions['themeConfig'] | undefined);
    get payload(): FormEndpointPayload | undefined;
    set payload(value: FormEndpointPayload | undefined);
    get formHandle(): string | undefined;
    set formHandle(value: string | undefined);
    get endpoint(): string | undefined;
    set endpoint(value: string | undefined);
    get staticCache(): boolean | undefined;
    set staticCache(value: boolean | undefined);
    get refreshTokens(): boolean | undefined;
    set refreshTokens(value: boolean | undefined);
    get locale(): string | undefined;
    set locale(value: string | undefined);
    get siteId(): number | undefined;
    set siteId(value: number | undefined);
    get autoVisible(): boolean | undefined;
    set autoVisible(value: boolean | undefined);
    get mode(): FormMountOptions["mode"] | undefined;
    set mode(value: FormMountOptions['mode'] | undefined);
    getInstance(): FormieFormInstance | null;
    submit(action?: FormAction): Promise<FormSubmitResult | null>;
    private buildOptions;
    private bindInstanceEvents;
    private scheduleMount;
    private mount;
    private unmount;
}
//# sourceMappingURL=form-element.d.ts.map