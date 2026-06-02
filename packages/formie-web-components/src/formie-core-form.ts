import {
    FRONTEND_CLIENT_EVENT_NAMES,
    createFrontendFormInstance,
    createGraphqlFrontendTransport,
    createRestFrontendTransport,
    loadFrontendEnvelope,
    loadGraphqlFrontendEnvelope,
    type FrontendFormInstance,
    type FrontendFormState,
} from '@verbb/formie-core';
import { LitElement } from 'lit';
import { html as staticHtml, unsafeStatic } from 'lit/static-html.js';
import { property, state } from 'lit/decorators.js';
import { assertValidCustomElementName, getFormieRegistry, type FormieRegistry } from './registry.js';
import { renderErrorView, renderFormView, renderLoadingView } from './render-view.js';

function st(tag: string) {
    assertValidCustomElementName(tag);

    return unsafeStatic(tag);
}

export class FormieCoreForm extends LitElement {
    @property({ type: String }) endpoint = '';

    @property({ type: String, attribute: 'form-handle' }) formHandle = '';

    @property({
        type: Number,
        attribute: 'site-id',
        converter: {
            fromAttribute(value: string | null): number | undefined {
                if (value == null || value === '') {
                    return undefined;
                }

                const n = Number(value);

                return Number.isFinite(n) ? n : undefined;
            },
        },
    })
    siteId?: number;

    @property({
        attribute: 'transport',
        converter: {
            fromAttribute(value: string | null): 'rest' | 'graphql' {
                const v = (value ?? 'rest').toLowerCase();

                return v === 'graphql' ? 'graphql' : 'rest';
            },
        },
    })
    transport: 'rest' | 'graphql' = 'rest';

    @property({
        attribute: 'fetch-credentials',
        converter: {
            fromAttribute(value: string | null): RequestCredentials {
                if (value === 'omit' || value === 'same-origin' || value === 'include') {
                    return value;
                }

                return 'same-origin';
            },
        },
    })
    fetchCredentials: RequestCredentials = 'same-origin';

    @property({ type: String, attribute: 'form-class' }) formClass = '';

    @property({ type: String, attribute: 'loading-message' }) loadingMessage = 'Loading form…';

    /** Per-instance UI registry (defaults to {@link getFormieRegistry}). */
    @property({ attribute: false }) registry: FormieRegistry | undefined;

    @state() private loadError: string | null = null;

    @state() private booting = false;

    @state() private snapshot: FrontendFormState | null = null;

    private instance: FrontendFormInstance | null = null;

    private unsubscribers: Array<() => void> = [];

    private loadGeneration = 0;

    override createRenderRoot(): HTMLElement | DocumentFragment {
        return this;
    }

    override disconnectedCallback(): void {
        super.disconnectedCallback();
        this.teardown();
    }

    override connectedCallback(): void {
        super.connectedCallback();
        void this.bootstrap(false);
    }

    protected override willUpdate(changed: Map<PropertyKey, unknown>): void {
        super.willUpdate(changed);

        if (!this.hasUpdated) {
            return;
        }

        const keys: PropertyKey[] = ['endpoint', 'formHandle', 'siteId', 'transport', 'fetchCredentials'];

        if (keys.some((k) => changed.has(k))) {
            void this.bootstrap(true);
        }
    }

    /** Form instance after a successful load; `null` while loading or after teardown. */
    getFormieInstance(): FrontendFormInstance | null {
        return this.instance;
    }

    /** Reload envelope + form instance from the server. */
    async reload(): Promise<void> {
        await this.bootstrap(true);
    }

    private get resolvedRegistry(): FormieRegistry {
        return this.registry ?? getFormieRegistry();
    }

    private teardown(): void {
        for (const u of this.unsubscribers) {
            u();
        }

        this.unsubscribers = [];
        void this.instance?.destroy();
        this.instance = null;
        this.snapshot = null;
    }

    private async bootstrap(force: boolean): Promise<void> {
        const generation = ++this.loadGeneration;

        const handle = this.formHandle.trim();
        /** Empty string is valid: core REST/GraphQL clients resolve relative to the current origin. */
        const ep = this.endpoint.trim();

        if (!handle) {
            this.teardown();
            this.booting = false;
            this.loadError = 'Set `form-handle` on <formie-core-form>.';

            return;
        }

        if (force) {
            this.teardown();
        } else if (this.instance) {
            return;
        }

        this.booting = true;
        this.loadError = null;

        const creds = this.fetchCredentials;
        const siteId = this.siteId;

        try {
            const envelopeOpts = {
                endpoint: ep,
                formHandle: handle,
                ...(siteId !== undefined ? { siteId } : {}),
                credentials: creds,
            };

            const envelope =
                this.transport === 'graphql'
                    ? await loadGraphqlFrontendEnvelope(envelopeOpts)
                    : await loadFrontendEnvelope(envelopeOpts);

            if (generation !== this.loadGeneration) {
                return;
            }

            const transport =
                this.transport === 'graphql'
                    ? createGraphqlFrontendTransport(envelopeOpts)
                    : createRestFrontendTransport(envelopeOpts);

            const instance = createFrontendFormInstance({ envelope, transport });

            this.instance = instance;

            for (const eventName of FRONTEND_CLIENT_EVENT_NAMES) {
                const off = instance.on(eventName, (detail) => {
                    this.dispatchEvent(
                        new CustomEvent(eventName, {
                            detail,
                            bubbles: true,
                            composed: true,
                        }),
                    );
                });
                this.unsubscribers.push(off);
            }

            this.unsubscribers.push(
                instance.subscribe((next) => {
                    this.snapshot = next;
                    this.requestUpdate();
                }),
            );

            this.booting = false;
        } catch (error) {
            if (generation !== this.loadGeneration) {
                return;
            }

            this.teardown();
            this.booting = false;
            this.loadError = error instanceof Error ? error.message : 'Unable to load the form.';
        }
    }

    protected override render() {
        const reg = this.resolvedRegistry;

        if (this.loadError) {
            const errTag = reg.regions.errorSummary;

            if (errTag) {
                const t = st(errTag);

                return staticHtml`<${t} .errors=${[this.loadError]} .kind=${'load'}></${t}>`;
            }

            return renderErrorView(this.loadError);
        }

        if (this.booting || !this.snapshot || !this.instance) {
            const loadTag = reg.regions.loading;

            if (loadTag) {
                const t = st(loadTag);

                return staticHtml`<${t} .message=${this.loadingMessage}></${t}>`;
            }

            return renderLoadingView(this.loadingMessage);
        }

        return renderFormView({
            registry: reg,
            state: this.snapshot,
            instance: this.instance,
            host: this,
            formClass: this.formClass || '',
        });
    }
}
