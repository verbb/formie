import { createFormieClient, FORMIE_HTML_EVENT_NAMES } from '@verbb/formie-browser';
import type {
    FormAction,
    FormEndpointPayload,
    FormEventUnsubscribe,
    FormMountOptions,
    FormieClient,
    FormieFormInstance,
    FormSubmitResult,
} from '@verbb/formie-browser';

export type FormieFormElementOptions = {
    baseUrl?: string;
} & Partial<FormMountOptions>;

function toBoolean(value: string | null): boolean {
    if (value == null || value === '') {
        return false;
    }

    const normalized = value.toLowerCase();
    return normalized === 'true' || normalized === '1';
}

function reflectStringAttribute(element: HTMLElement, name: string, value: string | undefined) {
    if (typeof value === 'string' && value.length > 0) {
        element.setAttribute(name, value);
    } else {
        element.removeAttribute(name);
    }
}

function reflectBooleanAttribute(element: HTMLElement, name: string, value: boolean | undefined) {
    if (value === true) {
        element.setAttribute(name, 'true');
    } else {
        element.removeAttribute(name);
    }
}

function resolveEndpoint(baseUrl: string, endpoint?: string) {
    if (!endpoint) {
        return undefined;
    }

    if (endpoint.startsWith('http')) {
        return endpoint;
    }

    return `${baseUrl}${endpoint}`;
}

export class FormieFormElement extends HTMLElement {
    static get observedAttributes() {
        return [
            'mode',
            'transport',
            'theme',
            'form-handle',
            'endpoint',
            'refresh-tokens',
            'static-cache',
            'locale',
            'site-id',
            'auto-visible',
            'base-url',
        ];
    }

    private client!: FormieClient;
    private mountRoot!: HTMLDivElement;
    private mountedInstance: FormieFormInstance | null = null;
    private optionState: FormieFormElementOptions = {};
    private mountScheduled: Promise<void> | null = null;
    private eventUnsubs: FormEventUnsubscribe[] = [];

    constructor() {
        super();
        // Spec: autonomous custom element constructors must not add attributes, children, or
        // mutate the tree. Keep this empty so `document.createElement('formie-form')` works.
    }

    /** Lazily create client + mount container (not in the constructor). */
    private ensureInitialized(): void {
        if (this.mountRoot) {
            return;
        }

        this.client = createFormieClient();
        this.mountRoot = document.createElement('div');
    }

    connectedCallback() {
        this.ensureInitialized();
        // Custom elements default to `display: inline`; set only after construction.
        this.style.display = 'block';
        if (!this.contains(this.mountRoot)) {
            this.append(this.mountRoot);
        }

        void this.scheduleMount();
    }

    disconnectedCallback() {
        void this.unmount();
    }

    attributeChangedCallback(name: string, oldValue: string | null, newValue: string | null) {
        if (oldValue === newValue || !this.isConnected) {
            return;
        }

        if (name === 'refresh-tokens' && this.optionState.refreshTokens !== undefined) {
            return;
        }

        if (name === 'static-cache' && this.optionState.staticCache !== undefined) {
            return;
        }

        void this.scheduleMount();
    }

    get baseUrl() {
        const attributeValue = this.getAttribute('base-url');

        return this.optionState.baseUrl ?? attributeValue ?? undefined;
    }

    set baseUrl(value: string | undefined) {
        this.optionState.baseUrl = value;
        reflectStringAttribute(this, 'base-url', value);
        void this.scheduleMount();
    }

    get transport() {
        const attributeValue = this.getAttribute('transport') as FormMountOptions['transport'] | null;

        return this.optionState.transport ?? attributeValue ?? undefined;
    }

    set transport(value: FormMountOptions['transport'] | undefined) {
        this.optionState.transport = value;
        reflectStringAttribute(this, 'transport', value);
        void this.scheduleMount();
    }

    get theme() {
        const attributeValue = this.getAttribute('theme') as FormMountOptions['theme'] | null;

        return this.optionState.theme ?? attributeValue ?? undefined;
    }

    set theme(value: FormMountOptions['theme'] | undefined) {
        this.optionState.theme = value;
        reflectStringAttribute(this, 'theme', value as string | undefined);
        void this.scheduleMount();
    }

    get themeConfig() {
        return this.optionState.themeConfig;
    }

    set themeConfig(value: FormMountOptions['themeConfig'] | undefined) {
        this.optionState.themeConfig = value;
        void this.scheduleMount();
    }

    get payload() {
        return this.optionState.payload as FormEndpointPayload | undefined;
    }

    set payload(value: FormEndpointPayload | undefined) {
        this.optionState.payload = value;
        void this.scheduleMount();
    }

    get formHandle() {
        const attributeValue = this.getAttribute('form-handle');

        return this.optionState.formHandle ?? attributeValue ?? undefined;
    }

    set formHandle(value: string | undefined) {
        this.optionState.formHandle = value;
        reflectStringAttribute(this, 'form-handle', value);
        void this.scheduleMount();
    }

    get endpoint() {
        const attributeValue = this.getAttribute('endpoint');

        return this.optionState.endpoint ?? attributeValue ?? undefined;
    }

    set endpoint(value: string | undefined) {
        this.optionState.endpoint = value;
        reflectStringAttribute(this, 'endpoint', value);
        void this.scheduleMount();
    }

    get staticCache() {
        return this.optionState.staticCache ?? (this.hasAttribute('static-cache') ? toBoolean(this.getAttribute('static-cache')) : undefined);
    }

    set staticCache(value: boolean | undefined) {
        this.optionState.staticCache = value;
        reflectBooleanAttribute(this, 'static-cache', value);
        void this.scheduleMount();
    }

    get refreshTokens() {
        return this.optionState.refreshTokens ?? (this.hasAttribute('refresh-tokens') ? toBoolean(this.getAttribute('refresh-tokens')) : undefined);
    }

    set refreshTokens(value: boolean | undefined) {
        this.optionState.refreshTokens = value;
        reflectBooleanAttribute(this, 'refresh-tokens', value);
        void this.scheduleMount();
    }

    get locale() {
        const attributeValue = this.getAttribute('locale');

        return this.optionState.locale ?? attributeValue ?? undefined;
    }

    set locale(value: string | undefined) {
        this.optionState.locale = value;
        reflectStringAttribute(this, 'locale', value);
        void this.scheduleMount();
    }

    get siteId() {
        return this.optionState.siteId ?? (this.getAttribute('site-id') ? Number(this.getAttribute('site-id')) : undefined);
    }

    set siteId(value: number | undefined) {
        this.optionState.siteId = value;
        reflectStringAttribute(this, 'site-id', typeof value === 'number' ? String(value) : undefined);
        void this.scheduleMount();
    }

    get autoVisible() {
        return this.optionState.autoVisible ?? (this.hasAttribute('auto-visible') ? toBoolean(this.getAttribute('auto-visible')) : undefined);
    }

    set autoVisible(value: boolean | undefined) {
        this.optionState.autoVisible = value;
        reflectBooleanAttribute(this, 'auto-visible', value);
        void this.scheduleMount();
    }

    get mode() {
        const attributeValue = this.getAttribute('mode') as FormMountOptions['mode'] | null;

        return this.optionState.mode ?? attributeValue ?? 'server-rendered';
    }

    set mode(value: FormMountOptions['mode'] | undefined) {
        this.optionState.mode = value;
        reflectStringAttribute(this, 'mode', value);
        void this.scheduleMount();
    }

    getInstance(): FormieFormInstance | null {
        this.ensureInitialized();

        return this.mountedInstance;
    }

    async submit(action: FormAction = 'submit'): Promise<FormSubmitResult | null> {
        this.ensureInitialized();

        if (!this.mountedInstance) {
            return null;
        }

        return this.mountedInstance.submit(action);
    }

    private buildOptions(): FormMountOptions {
        const baseUrl = this.baseUrl || '';
        const transport = this.transport;
        const defaultEndpoint = transport === 'graphql'
            ? '/api'
            : '/actions/formie/server/forms/render';
        const endpoint = resolveEndpoint(baseUrl, this.endpoint || defaultEndpoint);
        const staticCache = this.staticCache;
        const refreshTokens = this.refreshTokens;

        return {
            mode: this.mode,
            transport,
            theme: this.theme,
            themeConfig: this.themeConfig,
            formHandle: this.formHandle,
            endpoint,
            payload: this.payload,
            staticCache,
            refreshTokens,
            locale: this.locale,
            siteId: this.siteId,
            autoVisible: this.autoVisible ?? false,
        };
    }

    private bindInstanceEvents(instance: FormieFormInstance) {
        this.eventUnsubs.forEach((unsubscribe) => unsubscribe());
        this.eventUnsubs = FORMIE_HTML_EVENT_NAMES.map((eventName: string) => {
            return instance.on(eventName, (detail: unknown) => {
                this.dispatchEvent(new CustomEvent(eventName, {
                    detail,
                    bubbles: true,
                    composed: true,
                }));
            });
        });
    }

    private async scheduleMount(): Promise<void> {
        this.ensureInitialized();

        if (this.mountScheduled) {
            return this.mountScheduled;
        }

        this.mountScheduled = Promise.resolve().then(async() => {
            this.mountScheduled = null;

            if (!this.isConnected) {
                return;
            }

            await this.mount();
        });

        return this.mountScheduled;
    }

    private async mount(): Promise<void> {
        await this.unmount();

        const instance = await this.client.mount(this.mountRoot, this.buildOptions());
        this.mountedInstance = instance;
        this.bindInstanceEvents(instance);
        this.dispatchEvent(new CustomEvent('formie-mounted', {
            detail: {
                id: instance.id,
                instance,
            },
            bubbles: true,
            composed: true,
        }));
    }

    private async unmount(): Promise<void> {
        this.eventUnsubs.forEach((unsubscribe) => unsubscribe());
        this.eventUnsubs = [];

        if (!this.mountRoot || !this.mountedInstance) {
            return;
        }

        const instance = this.mountedInstance;
        await this.client.unmount(this.mountRoot);
        this.mountedInstance = null;
        this.dispatchEvent(new CustomEvent('formie-unmounted', {
            detail: {
                id: instance.id,
            },
            bubbles: true,
            composed: true,
        }));
    }
}
