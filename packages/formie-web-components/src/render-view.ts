import {
    compositePartDefinitions,
    createRepeaterRowValue,
    isCompositeField,
    isFileField,
    isKnownFrontendFieldType,
    isRepeatableField,
    repeaterRowDefinitions,
    type FrontendFieldDefinition,
    type FrontendFormDefinition,
    type FrontendFormInstance,
    type FrontendFormState,
    type FrontendRowDefinition,
} from '@verbb/formie-core';
import { html, nothing, type TemplateResult } from 'lit';
import { html as staticHtml, unsafeStatic } from 'lit/static-html.js';
import { ref } from 'lit/directives/ref.js';
import { unsafeHTML } from 'lit/directives/unsafe-html.js';
import { assertValidCustomElementName, type FormieRegistry } from './registry.js';
import { resolveFieldRendererType } from './field-utils.js';
import { FORMIE_CONTROL_VALUE_EVENT, type FormieFieldControlElement } from './types.js';
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

function st(tag: string) {
    assertValidCustomElementName(tag);

    return unsafeStatic(tag);
}

function bindRegistryControlHost(
    host: Element | undefined,
    registryTag: string,
    field: FrontendFieldDefinition,
    value: unknown,
    errorKey: string,
    disabled: boolean,
    hidden: boolean,
    onChange: (v: unknown) => void,
): void {
    if (!host || !(host instanceof HTMLElement)) {
        return;
    }

    const tagLower = registryTag.toLowerCase();
    let el = host.firstElementChild as FormieFieldControlElement | null;

    if (!el || el.tagName.toLowerCase() !== tagLower) {
        host.replaceChildren();
        el = document.createElement(registryTag) as FormieFieldControlElement;
        el.addEventListener(FORMIE_CONTROL_VALUE_EVENT, (e) => {
            onChange((e as CustomEvent<unknown>).detail);
        });
        host.append(el);
    }

    el.field = field;
    el.value = value;
    el.errorKey = errorKey;
    el.disabled = disabled;
    el.hidden = hidden;
}

function renderRegistryControl(
    ctx: RenderViewContext,
    registryTag: string,
    field: FrontendFieldDefinition,
    value: unknown,
    errorKey: string,
    disabled: boolean,
    hidden: boolean,
    onChange: (v: unknown) => void,
): TemplateResult {
    return html`<div
        class="starter-core-registry-host min-w-0"
        ${ref((host) => {
            bindRegistryControlHost(host, registryTag, field, value, errorKey, disabled, hidden, onChange);
        })}
    ></div>`;
}

function wrapField(
    ctx: RenderViewContext,
    field: FrontendFieldDefinition,
    errors: string[],
    control: TemplateResult,
    layout: 'default' | 'compositePart' = 'default',
): TemplateResult {
    if (layout === 'compositePart') {
        return wrapCompositePartField(field, errors, control);
    }

    const tag = ctx.registry.fieldTag;

    if (!tag) {
        return wrapDefaultField(field, errors, control);
    }

    const s = st(tag);

    return staticHtml`<${s} .field=${field} .errors=${errors}>${control}</${s}>`;
}

/** Matches Vue starter name-part layout: one bordered card on the parent, parts inside the grid. */
function wrapCompositePartField(field: FrontendFieldDefinition, errors: string[], control: TemplateResult): TemplateResult {
    return html`
        <div class="starter-component-subfield" data-formie-field-type=${field.type}>
            ${field.label
                ? html`<label class="starter-component-subfield-label">${field.label}</label>`
                : nothing}
            <div class="starter-component-injected-control grid gap-2 text-slate-900">${control}</div>
            ${errors.length > 0
                ? html`<ul class="grid gap-1 text-sm text-red-600">
                      ${errors.map((err) => html`<li>${err}</li>`)}
                  </ul>`
                : nothing}
        </div>
    `;
}

function wrapDefaultField(field: FrontendFieldDefinition, errors: string[], control: TemplateResult): TemplateResult {
    return html`
        <div class="starter-component-card" data-formie-field-type=${field.type}>
            ${field.label
                ? html`<label class="starter-component-label">${field.label}</label>`
                : nothing}
            ${field.instructions
                ? html`<p class="starter-component-help">${field.instructions}</p>`
                : nothing}
            <div class="starter-component-injected-control grid gap-2 text-slate-900">${control}</div>
            ${errors.length > 0
                ? html`<ul class="grid gap-1 text-sm text-red-600">
                      ${errors.map((err) => html`<li>${err}</li>`)}
                  </ul>`
                : nothing}
        </div>
    `;
}

function renderNestedInput(
    field: FrontendFieldDefinition,
    value: unknown,
    disabled: boolean,
    setValue: (v: unknown) => void,
): TemplateResult {
    const contract = field.input;

    if (field.type === 'multi-line-text') {
        return html`
            <textarea
                class="starter-component-control"
                .value=${typeof value === 'string' ? value : ''}
                ?disabled=${disabled}
                placeholder=${typeof contract.placeholder === 'string' ? contract.placeholder : ''}
                @input=${(e: Event) => {
                    setValue((e.target as HTMLTextAreaElement).value);
                }}
            ></textarea>
        `;
    }

    if (field.type === 'dropdown') {
        const options = Array.isArray(contract.options) ? (contract.options as Array<Record<string, unknown>>) : [];
        const multiple = contract.multiple === true;

        return html`
            <select
                class="starter-component-control"
                ?disabled=${disabled}
                multiple=${multiple}
                .value=${multiple ? undefined : typeof value === 'string' ? value : ''}
                @change=${(e: Event) => {
                    const target = e.target as HTMLSelectElement;

                    if (multiple) {
                        setValue(Array.from(target.selectedOptions).map((o) => o.value));
                    } else {
                        setValue(target.value);
                    }
                }}
            >
                ${options.map(
                    (option) => html`
                        <option
                            value=${String(option.value ?? '')}
                            ?disabled=${option.disabled === true}
                        >
                            ${String(option.label ?? option.value ?? '')}
                        </option>
                    `,
                )}
            </select>
        `;
    }

    const inputType =
        typeof contract.inputType === 'string'
            ? contract.inputType
            : field.type === 'email'
              ? 'email'
              : field.type === 'phone'
                ? 'tel'
                : field.type === 'number'
                  ? 'number'
                  : 'text';

    return html`
        <input
            class="starter-component-control"
            type=${inputType}
            .value=${typeof value === 'string' ? value : ''}
            ?disabled=${disabled}
            placeholder=${typeof contract.placeholder === 'string' ? contract.placeholder : ''}
            @input=${(e: Event) => {
                setValue((e.target as HTMLInputElement).value);
            }}
        />
    `;
}

function renderDefaultControl(ctx: RenderViewContext, props: FieldNodeProps): TemplateResult {
    const { field, value, errorKey, disabled, setValue } = props;
    const contract = field.input;
    const rendererType = resolveFieldRendererType(field);

    if (isCompositeField(field)) {
        return renderComposite(ctx, props);
    }

    if (isRepeatableField(field)) {
        return renderRepeater(ctx, props);
    }

    if (isFileField(field)) {
        return renderFile(field, value, disabled, setValue);
    }

    if (rendererType === 'signature') {
        return html`<formie-internal-signature
            .field=${field}
            .modules=${ctx.state.definition.modules}
            .value=${typeof value === 'string' ? value : ''}
            ?disabled=${disabled}
            @formie-control-value-change=${(e: Event) => {
                setValue((e as CustomEvent<string>).detail);
            }}
        ></formie-internal-signature>`;
    }

    if (rendererType === 'multi-line-text' || rendererType === 'dropdown') {
        return renderNestedInput(field, value, disabled, setValue);
    }

    if (rendererType === 'radio') {
        const options = Array.isArray(contract.options) ? (contract.options as Array<Record<string, unknown>>) : [];

        return html`
            <div class="flex flex-col gap-2">
                ${options.map((option) => {
                    const optionValue = String(option.value ?? '');

                    return html`
                        <label class="flex items-center gap-2 text-sm text-slate-800">
                            <input
                                type="radio"
                                name=${`${field.id}-radio`}
                                .checked=${value === optionValue}
                                ?disabled=${disabled}
                                @change=${() => {
                                    setValue(optionValue);
                                }}
                            />
                            <span>${String(option.label ?? optionValue)}</span>
                        </label>
                    `;
                })}
            </div>
        `;
    }

    if (rendererType === 'checkboxes') {
        const options = Array.isArray(contract.options) ? (contract.options as Array<Record<string, unknown>>) : [];
        const selected = Array.isArray(value) ? value.map((x) => String(x)) : [];

        return html`
            <div class="flex flex-col gap-2">
                ${options.map((option) => {
                    const optionValue = String(option.value ?? '');
                    const checked = selected.includes(optionValue);

                    return html`
                        <label class="flex items-center gap-2 text-sm text-slate-800">
                            <input
                                type="checkbox"
                                .checked=${checked}
                                ?disabled=${disabled}
                                @change=${() => {
                                    const next = checked
                                        ? selected.filter((x) => x !== optionValue)
                                        : [...selected, optionValue];

                                    setValue(next);
                                }}
                            />
                            <span>${String(option.label ?? optionValue)}</span>
                        </label>
                    `;
                })}
            </div>
        `;
    }

    if (rendererType === 'agree') {
        const descriptionHtml = typeof contract.descriptionHtml === 'string' ? contract.descriptionHtml : null;

        return html`
            <label class="flex items-start gap-2 text-sm text-slate-800">
                <input
                    type="checkbox"
                    .checked=${value === true}
                    ?disabled=${disabled}
                    @change=${(e: Event) => {
                        setValue((e.target as HTMLInputElement).checked);
                    }}
                />
                <span>${descriptionHtml ? unsafeHTML(descriptionHtml) : field.label ?? ''}</span>
            </label>
        `;
    }

    if (!isKnownFrontendFieldType(rendererType)) {
        return html`<div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
            Unknown field type:
            ${String(field.meta?.fieldType ?? field.type)}
        </div>`;
    }

    return renderNestedInput(field, value, disabled, setValue);
}

function renderFile(
    field: FrontendFieldDefinition,
    value: unknown,
    disabled: boolean,
    setValue: (v: unknown) => void,
): TemplateResult {
    const contract = field.input;
    const files = Array.isArray(value) ? value : [];
    const multiple = contract.multiple === true;
    const items = files.map((entry, index) => {
        if (entry && typeof entry === 'object' && 'name' in entry && typeof entry.name === 'string') {
            return entry.name;
        }

        if (entry && typeof entry === 'object' && 'filename' in entry && typeof entry.filename === 'string') {
            return entry.filename;
        }

        if (entry && typeof entry === 'object' && 'assetId' in entry && typeof entry.assetId === 'number') {
            return `Asset #${entry.assetId}`;
        }

        return `File ${index + 1}`;
    });

    return html`
        <div class="grid gap-2">
            <input
                type="file"
                class="starter-component-control"
                ?disabled=${disabled}
                multiple=${multiple}
                @change=${(e: Event) => {
                    const t = e.target as HTMLInputElement;

                    setValue(Array.from(t.files || []));
                }}
            />
            ${items.length > 0
                ? html`<ul class="grid gap-1 text-sm text-slate-600">
                      ${items.map((item) => html`<li>${item}</li>`)}
                  </ul>`
                : nothing}
        </div>
    `;
}

function renderComposite(ctx: RenderViewContext, props: FieldNodeProps): TemplateResult {
    const { field, value, errorKey, disabled, setValue } = props;
    const parts = compositePartDefinitions(field);
    const current = value && typeof value === 'object' ? (value as Record<string, unknown>) : {};

    if (parts.length === 0) {
        return html`<div class="text-sm text-amber-800">Composite field has no parts.</div>`;
    }

    return html`
        <div class="starter-component-name-grid">
            ${parts
                .filter((part) => part.meta?.hidden !== true)
                .map((part) => {
                    const partErrorKey = `${errorKey}.${part.handle}`;

                    return renderFieldNode(
                        ctx,
                        {
                            field: part,
                            value: current[part.handle],
                            errors: ctx.state.errors.fields[partErrorKey] || [],
                            errorKey: partErrorKey,
                            disabled: disabled || part.meta?.disabled === true,
                            setValue(next) {
                                setValue({
                                    ...current,
                                    [part.handle]: next,
                                });
                            },
                        },
                        'compositePart',
                    );
                })}
        </div>
    `;
}

function renderRepeater(ctx: RenderViewContext, props: FieldNodeProps): TemplateResult {
    const { field, value, errorKey, disabled, setValue } = props;
    const rows = repeaterRowDefinitions(field);
    const currentRows = Array.isArray(value) ? (value as Array<Record<string, unknown>>) : [];
    const contract = field.input;
    const minRows = Number(contract.minRows ?? 0) || 0;
    const maxRows = Number(contract.maxRows ?? 0) || 0;
    const canAdd = !disabled && (maxRows <= 0 || currentRows.length < maxRows);

    if (rows.length === 0) {
        return html`<div class="text-sm text-amber-800">Repeater has no row layout.</div>`;
    }

    return html`
        <div class="grid gap-4" data-formie-repeater-container>
            ${currentRows.map((rowValue, rowIndex) => {
                return html`
                    <div class="rounded-xl border border-slate-200 p-4" data-formie-repeater-item>
                        ${rows.map((row, nestedRowIndex) => {
                            return renderNestedRow(ctx, row, rowValue, `${errorKey}.${rowIndex}`, disabled, (rowField, next) => {
                                const nextRows = currentRows.map((c, i) => {
                                    if (i !== rowIndex) {
                                        return c;
                                    }

                                    return { ...c, [rowField.handle]: next };
                                });

                                setValue(nextRows);
                            });
                        })}
                        <button
                            type="button"
                            class="mt-2 rounded-lg border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-700"
                            ?disabled=${disabled || (minRows > 0 && currentRows.length <= minRows)}
                            @click=${() => {
                                setValue(currentRows.filter((_, i) => i !== rowIndex));
                            }}
                        >
                            Remove
                        </button>
                    </div>
                `;
            })}
            <button
                type="button"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm"
                ?disabled=${!canAdd}
                @click=${() => {
                    setValue([...currentRows, createRepeaterRowValue(field)]);
                }}
            >
                ${String(contract.addLabel ?? 'Add another row')}
            </button>
        </div>
    `;
}

type FieldNodeProps = {
    field: FrontendFieldDefinition;
    value: unknown;
    errors: string[];
    errorKey: string;
    disabled: boolean;
    setValue(v: unknown): void;
};

function renderFieldNode(
    ctx: RenderViewContext,
    props: FieldNodeProps,
    fieldLayout: 'default' | 'compositePart' = 'default',
): TemplateResult {
    const { field, value, errors, errorKey, disabled, setValue } = props;
    const fieldState = ctx.state.fieldStates[field.id];
    const hidden = fieldState?.hidden === true;

    if (hidden) {
        return html``;
    }

    const rendererType = resolveFieldRendererType(field);
    const regTag =
        ctx.registry.fieldControls[field.type] || ctx.registry.fieldControls[rendererType] || null;

    const syncSet = (v: unknown) => {
        setValue(v);
        ctx.host.requestUpdate();
    };

    const inner = regTag
        ? renderRegistryControl(ctx, regTag, field, value, errorKey, disabled, hidden, syncSet)
        : renderDefaultControl(ctx, { ...props, setValue: syncSet });

    return wrapField(ctx, field, errors, inner, fieldLayout);
}

function renderNestedRow(
    ctx: RenderViewContext,
    row: FrontendRowDefinition,
    values: Record<string, unknown>,
    errorPrefix: string,
    disabled: boolean | undefined,
    setFieldValue: (f: FrontendFieldDefinition, v: unknown) => void,
): TemplateResult {
    return html`
        <div class="starter-core-row grid gap-4">
            ${row.fields.map((field) => {
                const ek = `${errorPrefix}.${field.handle}`;

                return renderFieldNode(ctx, {
                    field,
                    value: values[field.handle],
                    errors: ctx.state.errors.fields[ek] || [],
                    errorKey: ek,
                    disabled: disabled === true || ctx.state.fieldStates[field.id]?.disabled === true,
                    setValue(v) {
                        setFieldValue(field, v);
                    },
                });
            })}
        </div>
    `;
}

function renderTopLevelRow(ctx: RenderViewContext, row: FrontendRowDefinition): TemplateResult {
    return html`
        <div class="starter-core-row grid gap-4">
            ${row.fields.map((field) => {
                return renderFieldNode(ctx, {
                    field,
                    value: ctx.state.values[field.id],
                    errors: ctx.state.errors.fields[field.id] || [],
                    errorKey: field.id,
                    disabled: ctx.state.fieldStates[field.id]?.disabled === true,
                    setValue(v) {
                        ctx.instance.setValue(field.id, v);
                    },
                });
            })}
        </div>
    `;
}

function renderPageActions(ctx: RenderViewContext): TemplateResult {
    const page = ctx.state.definition.pages.find((p) => p.id === ctx.state.currentPageId);

    if (!page) {
        return html``;
    }

    const pageActionsTag = ctx.registry.regions.pageActions;

    if (pageActionsTag) {
        const t = st(pageActionsTag);

        return staticHtml`<${t}
            .page=${page}
            .state=${ctx.state}
            .instance=${ctx.instance}
        ></${t}>`;
    }

    const secondary = page.actions.secondary.map(
        (action) => html`
            <button
                type="button"
                @click=${() => {
                    void ctx.instance.submit(action.type);
                }}
            >
                ${action.label}
            </button>
        `,
    );

    return html`
        <div class="formie-page-actions">
            ${secondary}
            <button type="submit">${page.actions.primary.label}</button>
        </div>
    `;
}

export function renderFormView(ctx: RenderViewContext): TemplateResult {
    const page =
        ctx.state.definition.pages.find((p) => {
            return p.id === ctx.state.currentPageId && ctx.state.pageStates[p.id]?.hidden !== true;
        }) ||
        ctx.state.definition.pages.find((p) => ctx.state.pageStates[p.id]?.hidden !== true) ||
        ctx.state.definition.pages[0];

    if (!page) {
        return html``;
    }

    const formErrors = ctx.state.errors.form;
    const errorMessage = ctx.state.lastSubmitResult?.messages.error;
    const shouldStandaloneErr = !!errorMessage && !formErrors.includes(errorMessage);

    const formInner = html`
        ${formErrors.length > 0
            ? html`<div class="starter-core-msg starter-core-msg-error mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                  <ul class="list-inside list-disc">
                      ${formErrors.map((e) => html`<li>${e}</li>`)}
                  </ul>
              </div>`
            : nothing}
        ${ctx.state.lastSubmitResult?.messages.notice
            ? html`<div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                  ${ctx.state.lastSubmitResult.messages.notice}
              </div>`
            : nothing}
        ${shouldStandaloneErr
            ? html`<div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">${errorMessage}</div>`
            : nothing}
        ${renderFormPage(ctx, page)}
    `;

    const renderId = ctx.state.session.tokens.render ?? '';

    return html`
        <form
            class=${ctx.formClass || 'starter-component-form starter-core-preview text-slate-900'}
            data-formie-definition=${ctx.state.definition.handle}
            data-formie-render-id=${renderId}
            @submit=${(e: Event) => {
                e.preventDefault();
                void ctx.instance.submit();
            }}
        >
            ${formInner}
        </form>
    `;
}

function renderFormPage(
    ctx: RenderViewContext,
    page: FrontendFormDefinition['pages'][number],
): TemplateResult {
    const pageRegionTag = ctx.registry.regions.page;

    const pageBody = html`
        <div class="starter-core-fields grid gap-4">
            ${page.rows.map((row) => renderTopLevelRow(ctx, row))}
        </div>
        ${renderPageActions(ctx)}
    `;

    if (pageRegionTag) {
        const t = st(pageRegionTag);

        return staticHtml`<${t} .page=${page} .state=${ctx.state}>${pageBody}</${t}>`;
    }

    return html`
        <section data-page-id=${page.id} class="starter-core-page space-y-4">
            ${pageBody}
        </section>
    `;
}

export function renderLoadingView(message = 'Loading form…'): TemplateResult {
    return html`<div class="mt-3 text-sm text-slate-500">${message}</div>`;
}

export function renderErrorView(message: string): TemplateResult {
    return html`<div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">${message}</div>`;
}
