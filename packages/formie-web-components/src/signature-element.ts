import { html, LitElement, css } from 'lit';
import { property, state } from 'lit/decorators.js';
import type { FrontendFieldDefinition, FrontendFormDefinition } from '@verbb/formie-core';
import { FORMIE_CONTROL_VALUE_EVENT } from './types.js';

/** Internal `<formie-internal-signature>` used by `<formie-core-form>` for draw-signature fields. */
export class FormieInternalSignature extends LitElement {
    static override styles = css`
        :host {
            display: block;
        }
        .wrap {
            display: grid;
            gap: 0.5rem;
        }
        canvas {
            width: 100%;
            height: 12rem;
            border-radius: 0.75rem;
            border: 1px dashed #fda4af;
            background: linear-gradient(180deg, #fff1f2 0%, #fff 100%);
        }
        button {
            justify-self: start;
            border-radius: 0.75rem;
            border: 1px solid #cbd5e1;
            background: #fff;
            padding: 0.45rem 0.85rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #334155;
            cursor: pointer;
        }
        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        .err {
            font-size: 0.875rem;
            color: #b91c1c;
        }
    `;

    @property({ attribute: false })
    field!: FrontendFieldDefinition;

    @property({ attribute: false })
    modules: FrontendFormDefinition['modules'] = [];

    @property({ type: String })
    value = '';

    @property({ type: Boolean })
    disabled = false;

    @state()
    private loadError: string | null = null;

    private pad: {
        clear: () => void;
        isEmpty: () => boolean;
        toDataURL: () => string;
        fromDataURL: (v: string) => void;
        addEventListener?: (n: string, fn: () => void) => void;
        removeEventListener?: (n: string, fn: () => void) => void;
    } | null = null;

    private strokeListener = (): void => {
        this.emitValue();
    };

    override async firstUpdated(): Promise<void> {
        const canvas = this.shadowRoot?.querySelector('canvas');

        if (!canvas || !(canvas instanceof HTMLCanvasElement)) {
            return;
        }

        try {
            const { default: SignaturePad } = await import('signature_pad');
            const moduleConfig = this.resolveDrawModuleConfig();
            const backgroundColor =
                typeof moduleConfig?.options === 'object' &&
                moduleConfig.options &&
                typeof (moduleConfig.options as Record<string, unknown>).backgroundColor === 'string'
                    ? String((moduleConfig.options as Record<string, unknown>).backgroundColor)
                    : '#ffffff';
            const penColor =
                typeof moduleConfig?.options === 'object' &&
                moduleConfig.options &&
                typeof (moduleConfig.options as Record<string, unknown>).penColor === 'string'
                    ? String((moduleConfig.options as Record<string, unknown>).penColor)
                    : '#000000';
            const penWeight =
                typeof moduleConfig?.options === 'object' && moduleConfig.options
                    ? Number((moduleConfig.options as Record<string, unknown>).penWeight ?? 2) || 2
                    : 2;

            const pad = new SignaturePad(canvas, {
                backgroundColor,
                penColor,
                minWidth: penWeight,
                maxWidth: penWeight,
            }) as unknown as NonNullable<FormieInternalSignature['pad']>;

            this.pad = pad;
            pad.addEventListener?.('endStroke', this.strokeListener);
            this.resizeCanvas(canvas);
            window.addEventListener('resize', this.onWinResize);
            this.applySerializedValue();
        } catch (e) {
            this.loadError = e instanceof Error ? e.message : 'Signature pad failed to load.';
        }
    }

    override disconnectedCallback(): void {
        super.disconnectedCallback();
        window.removeEventListener('resize', this.onWinResize);

        if (this.pad?.removeEventListener) {
            this.pad.removeEventListener('endStroke', this.strokeListener);
        }

        this.pad = null;
    }

    private onWinResize = (): void => {
        const canvas = this.shadowRoot?.querySelector('canvas');

        if (canvas instanceof HTMLCanvasElement) {
            this.resizeCanvas(canvas);
        }
    };

    private resolveDrawModuleConfig(): { options?: Record<string, unknown> } | null {
        const refs = new Set(this.field.moduleRefs || []);
        const mod = this.modules.find((m) => {
            return refs.has(m.id) && m.capability === 'draw-signature';
        });

        return mod && typeof mod.config === 'object' && mod.config
            ? (mod.config as { options?: Record<string, unknown> })
            : null;
    }

    private resizeCanvas(canvas: HTMLCanvasElement): void {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const width = Math.max(1, Math.floor(canvas.clientWidth || 480));
        const height = 192;
        const ctx = canvas.getContext('2d');

        canvas.width = width * ratio;
        canvas.height = height * ratio;
        ctx?.scale(ratio, ratio);
        canvas.style.width = `${width}px`;
        canvas.style.height = `${height}px`;
        this.pad?.fromDataURL?.(this.value || 'data:,');
    }

    override updated(changed: Map<string, unknown>): void {
        if (changed.has('value') && this.pad) {
            this.applySerializedValue();
        }

        if (changed.has('disabled') && this.pad) {
            const canvas = this.shadowRoot?.querySelector('canvas');

            if (canvas instanceof HTMLElement) {
                canvas.style.pointerEvents = this.disabled ? 'none' : '';
            }
        }
    }

    private applySerializedValue(): void {
        if (!this.pad) {
            return;
        }

        if (!this.value) {
            if (!this.pad.isEmpty()) {
                this.pad.clear();
            }

            return;
        }

        try {
            this.pad.fromDataURL(this.value);
        } catch {
            /* ignore */
        }
    }

    private emitValue(): void {
        if (!this.pad || this.pad.isEmpty()) {
            this.dispatchEvent(
                new CustomEvent(FORMIE_CONTROL_VALUE_EVENT, {
                    detail: '',
                    bubbles: true,
                    composed: true,
                }),
            );

            return;
        }

        this.dispatchEvent(
            new CustomEvent(FORMIE_CONTROL_VALUE_EVENT, {
                detail: this.pad.toDataURL(),
                bubbles: true,
                composed: true,
            }),
        );
    }

    override render() {
        if (this.loadError) {
            return html`<div class="err">${this.loadError}</div>`;
        }

        return html`
            <div class="wrap">
                <canvas></canvas>
                <button type="button" ?disabled=${this.disabled} @click=${() => {
                    this.pad?.clear();
                    this.dispatchEvent(
                        new CustomEvent(FORMIE_CONTROL_VALUE_EVENT, {
                            detail: '',
                            bubbles: true,
                            composed: true,
                        }),
                    );
                }}>Clear</button>
            </div>
        `;
    }
}
