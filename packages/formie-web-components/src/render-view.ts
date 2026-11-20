import { clientActionAllowed } from '@verbb/formie-core';
import {
    compositePartDefinitions,
    createRepeaterRowValue,
    createTableRowValue,
    dropdownOptions,
    fieldOptions,
    focusClientFormResult,
    getClientPageProgress,
    getClientString,
    showsClientPageTitle,
    isArrayValuedField,
    isCalculationField,
    isFieldLabelVisuallyHidden,
    isPhoneCountryEnabled,
    addressPartMarker,
    isAddressAutocompletePart,
    addressAutocompleteMarkup,
    readAddressParts,
    isAddressField,
    addressStatePart,
    isDynamicAddressStatePart,
    loadAddressSubdivisions,
    isSearchableDropdown,
    searchableDropdownMarkup,
    searchableDropdownValue,
    isDatePickerField,
    isDateRangePickerField,
    dateRangePickerMarkup,
    dateRangePickerValue,
    datePickerInputValue,
    datePickerValue,
    isRichTextField,
    customFieldMarkup,
    readCustomFieldControls,
    textLimitCounter,
    type TextLimitCounter,
    checkboxToggle,
    allOptionsSelected,
    toggleAllOptions,
    moveListItem,
    surveyLikertRows,
    surveyRankOrder,
    attachPointerReorder,
    paymentFieldMarkup,
    uploadManagerMarkup,
    fieldContentPath,
    readUploadManagerEntries,
    summaryFieldMarkup,
    PHONE_COUNTRY_CHANGE_EVENT,
    phoneFieldValue,
    readPhoneFieldControls,
    tableColumns,
    VISUALLY_HIDDEN_CSS,
    type ClientTableColumn,
    fieldValueStructure,
    getClientErrorAriaLive,
    getClientFieldIds,
    getClientFieldLabelTarget,
    joinIdReferences,
    isCompositeField,
    isFileField,
    displayFieldContent,
    isFieldLabelHidden,
    isKnownClientFieldType,
    isRepeatableField,
    repeaterRowDefinitions,
    type ClientFieldDefinition,
    type ClientFieldElementIds,
    type ClientErrorAriaLive,
    type ClientFormDefinition,
    type ClientFormInstance,
    type ClientFormState,
    type ClientRowDefinition,
} from '@verbb/formie-core';
import { html, nothing, type TemplateResult } from 'lit';
import { html as staticHtml, unsafeStatic } from 'lit/static-html.js';
import { ref } from 'lit/directives/ref.js';
import { repeat } from 'lit/directives/repeat.js';
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
    state: ClientFormState;
    instance: ClientFormInstance;
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
    field: ClientFieldDefinition,
    value: unknown,
    errorKey: string,
    errors: string[],
    ids: ClientFieldElementIds,
    errorAriaLive: ClientErrorAriaLive,
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
    el.errors = errors;
    el.errorId = ids.errors;
    el.inputId = ids.input;
    el.labelId = ids.label;
    el.instructionsId = ids.instructions;
    el.errorAriaLive = errorAriaLive;
    el.disabled = disabled;
    el.hidden = hidden;
}

function renderRegistryControl(
    ctx: RenderViewContext,
    registryTag: string,
    field: ClientFieldDefinition,
    value: unknown,
    errorKey: string,
    errors: string[],
    ids: ClientFieldElementIds,
    errorAriaLive: ClientErrorAriaLive,
    disabled: boolean,
    hidden: boolean,
    onChange: (v: unknown) => void,
): TemplateResult {
    return html`<div
        class="starter-core-registry-host min-w-0"
        ${ref((host) => {
            bindRegistryControlHost(host, registryTag, field, value, errorKey, errors, ids, errorAriaLive, disabled, hidden, onChange);
        })}
    ></div>`;
}

function wrapField(
    ctx: RenderViewContext,
    field: ClientFieldDefinition,
    errors: string[],
    ids: ClientFieldElementIds,
    errorAriaLive: ClientErrorAriaLive,
    control: TemplateResult,
    layout: 'default' | 'compositePart' = 'default',
): TemplateResult {
    if (layout === 'compositePart') {
        return wrapCompositePartField(field, errors, ids, errorAriaLive, control);
    }

    const tag = ctx.registry.fieldTag;

    if (!tag) {
        return wrapDefaultField(field, errors, ids, errorAriaLive, control);
    }

    const s = st(tag);

    return staticHtml`<${s} .field=${field} .errors=${errors} .errorId=${ids.errors} .inputId=${ids.input} .labelId=${ids.label} .instructionsId=${ids.instructions} .errorAriaLive=${errorAriaLive}>${control}</${s}>`;
}

// A label in the Hidden position still names its control.
function labelStyle(field: ClientFieldDefinition) {
    return isFieldLabelVisuallyHidden(field) ? VISUALLY_HIDDEN_CSS : nothing;
}

function labelFor(field: ClientFieldDefinition, ids: ClientFieldElementIds) {
    return getClientFieldLabelTarget(field, resolveFieldRendererType(field)) === 'control' ? ids.input : nothing;
}

/** Matches Vue starter name-part layout: one bordered card on the parent, parts inside the grid. */
function wrapCompositePartField(field: ClientFieldDefinition, errors: string[], ids: ClientFieldElementIds, errorAriaLive: ClientErrorAriaLive, control: TemplateResult): TemplateResult {
    return html`
        <div class="starter-component-subfield" data-formie-field-type=${field.type} data-formie-field-uid=${field.uid} data-formie-field-handle=${field.handle} data-formie-field-required=${field.required ? 'true' : nothing}>
            ${field.label && !isFieldLabelHidden(field)
                ? html`<label class="starter-component-subfield-label" style=${labelStyle(field)} id=${ids.label} for=${labelFor(field, ids)}>${field.label}</label>`
                : nothing}
            <div class="starter-component-injected-control grid gap-2 text-slate-900">${control}</div>
            <ul id=${ids.errors} data-formie-field-errors aria-live=${errorAriaLive === 'off' ? nothing : errorAriaLive} aria-atomic=${errorAriaLive === 'off' ? nothing : 'true'} style=${errors.length === 0 ? 'position: absolute' : nothing} class="grid gap-1 text-sm text-red-600">
                ${errors.map((err) => html`<li>${err}</li>`)}
            </ul>
        </div>
    `;
}

function wrapDefaultField(field: ClientFieldDefinition, errors: string[], ids: ClientFieldElementIds, errorAriaLive: ClientErrorAriaLive, control: TemplateResult): TemplateResult {
    return html`
        <div class="starter-component-card" data-formie-field-type=${field.type} data-formie-field-uid=${field.uid} data-formie-field-handle=${field.handle} data-formie-field-required=${field.required ? 'true' : nothing}>
            ${field.label && !isFieldLabelHidden(field)
                ? html`<label class="starter-component-label" style=${labelStyle(field)} id=${ids.label} for=${labelFor(field, ids)}>${field.label}</label>`
                : nothing}
            ${field.instructions
                ? html`<div class="starter-component-help" id=${ids.instructions}>${unsafeHTML(field.instructions)}</div>`
                : nothing}
            <div class="starter-component-injected-control grid gap-2 text-slate-900">${control}</div>
            <ul id=${ids.errors} data-formie-field-errors aria-live=${errorAriaLive === 'off' ? nothing : errorAriaLive} aria-atomic=${errorAriaLive === 'off' ? nothing : 'true'} style=${errors.length === 0 ? 'position: absolute' : nothing} class="grid gap-1 text-sm text-red-600">
                ${errors.map((err) => html`<li>${err}</li>`)}
            </ul>
        </div>
    `;
}

type ControlLabelling = {
    errors: string[];
    ids: ClientFieldElementIds;
    // The default field wrapper renders the label and instructions these IDs point to. A custom field
    // element may not, so controls fall back to naming themselves.
    labelledByWrapper: boolean;
};

function instructionsReference(field: ClientFieldDefinition, labelling: ControlLabelling) {
    return labelling.labelledByWrapper && field.instructions ? labelling.ids.instructions : null;
}

function describedBy(field: ClientFieldDefinition, labelling: ControlLabelling) {
    return joinIdReferences(instructionsReference(field, labelling), labelling.errors.length > 0 && labelling.ids.errors) ?? nothing;
}

function ownName(field: ClientFieldDefinition, labelling: ControlLabelling) {
    return labelling.labelledByWrapper ? nothing : field.label || field.handle;
}

function autocomplete(field: ClientFieldDefinition) {
    return typeof field.input.autocomplete === 'string' ? field.input.autocomplete : nothing;
}

// Address auto-complete providers find each part of the address by its marker. Lit can't bind an attribute whose
// name varies, so the marker is set on the element itself.
function addressMarker(field: ClientFieldDefinition) {
    const marker = addressPartMarker(field);

    return ref((element) => {
        if (marker && element) element.setAttribute(marker, 'true');
    });
}

function renderNestedInput(
    field: ClientFieldDefinition,
    value: unknown,
    disabled: boolean,
    setValue: (v: unknown) => void,
    labelling: ControlLabelling,
    rendererType = resolveFieldRendererType(field),
): TemplateResult {
    const contract = field.input;
    const errors = labelling.errors;
    const errorId = labelling.ids.errors;

    if (rendererType === 'multi-line-text' && isRichTextField(field)) {
        // The editor builds itself inside the empty container and writes its HTML into the hidden textarea, then fires
        // `input`, which the wrapper reads, as in server-rendered forms. Lit never adds children to the container.
        return html`
            <div class="starter-component-rich-text" @input=${(e: Event) => {
                if (e.target instanceof HTMLTextAreaElement) setValue(e.target.value);
            }}>
                <div data-formie-rich-text></div>
                <textarea
                    id=${labelling.ids.input}
                    data-formie-multi-line-text-input
                    style="display: none"
                    aria-label=${ownName(field, labelling)}
                    aria-required=${field.required ? 'true' : nothing}
                    aria-invalid=${errors.length > 0 ? "true" : nothing}
                    aria-errormessage=${errors.length > 0 ? errorId : nothing}
                    aria-describedby=${describedBy(field, labelling)}
                    .value=${typeof value === 'string' ? value : ''}
                    ?disabled=${disabled}
                    placeholder=${typeof contract.placeholder === 'string' ? contract.placeholder : ''}
                ></textarea>
            </div>
        `;
    }

    if (rendererType === 'multi-line-text') {
        return html`
            <textarea
            id=${labelling.ids.input}
            aria-label=${ownName(field, labelling)}
            aria-required=${field.required ? 'true' : nothing}
            aria-invalid=${errors.length > 0 ? "true" : nothing}
            aria-errormessage=${errors.length > 0 ? errorId : nothing}
            aria-describedby=${describedBy(field, labelling)}
            autocomplete=${autocomplete(field)}
                class="starter-component-control"
                ${addressMarker(field)}
                .value=${typeof value === 'string' ? value : ''}
                ?disabled=${disabled}
                placeholder=${typeof contract.placeholder === 'string' ? contract.placeholder : ''}
                @input=${(e: Event) => {
                    setValue((e.target as HTMLTextAreaElement).value);
                }}
            ></textarea>
        `;
    }

    if (rendererType === 'dropdown' && isSearchableDropdown(field)) {
        // The searchable dropdown hides this select and edits its options, so it's HTML Lit leaves alone while the
        // definition stays the same. It mirrors each change back to the select and fires `input`, which the wrapper reads.
        return html`
            <div class="starter-component-combobox" @input=${(e: Event) => {
                if (e.target instanceof HTMLSelectElement) setValue(searchableDropdownValue(field, e.target));
            }}>
                <div>${unsafeHTML(searchableDropdownMarkup(field, { id: labelling.ids.input, 'aria-required': field.required ? 'true' : undefined, disabled, ...(addressPartMarker(field) ? { [addressPartMarker(field)!]: true } : {}) }))}</div>
            </div>
        `;
    }

    if (rendererType === 'dropdown') {
        const multiple = contract.multiple === true;
        // Element fields keep a list of IDs even when only one can be chosen.
        const listValue = isArrayValuedField(field);
        const selected = (Array.isArray(value) ? value : (value == null ? [] : [value])).map((item) => String(item));
        const options = multiple ? fieldOptions(field) : dropdownOptions(field);
        const current = selected[0] ?? '';
        // Without this, a dropdown whose answer was cleared would show its first option instead.
        const showEmpty = !multiple && current === '' && !options.some((option) => option.value === '');

        return html`
            <select
            ${addressMarker(field)}
            id=${labelling.ids.input}
            aria-label=${ownName(field, labelling)}
            aria-required=${field.required ? 'true' : nothing}
            aria-invalid=${errors.length > 0 ? "true" : nothing}
            aria-errormessage=${errors.length > 0 ? errorId : nothing}
            aria-describedby=${describedBy(field, labelling)}
            autocomplete=${autocomplete(field)}
                class="starter-component-control"
                ?disabled=${disabled}
                ?multiple=${multiple}
                @change=${(e: Event) => {
                    const target = e.target as HTMLSelectElement;

                    if (multiple) {
                        setValue(Array.from(target.selectedOptions).map((o) => o.value));
                    } else {
                        setValue(listValue ? (target.value === '' ? [] : [target.value]) : target.value);
                    }
                }}
            >
                ${showEmpty ? html`<option value="" disabled hidden .selected=${true}></option>` : nothing}
                ${options.map(
                    (option) => html`
                        <option
                            value=${option.value}
                            ?disabled=${option.disabled}
                            .selected=${multiple ? selected.includes(option.value) : current === option.value}
                        >
                            ${option.label}
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
            id=${labelling.ids.input}
            aria-label=${ownName(field, labelling)}
            aria-required=${field.required ? 'true' : nothing}
            aria-invalid=${errors.length > 0 ? "true" : nothing}
            aria-errormessage=${errors.length > 0 ? errorId : nothing}
            aria-describedby=${describedBy(field, labelling)}
            autocomplete=${autocomplete(field)}
            class="starter-component-control"
            ${addressMarker(field)}
            type=${inputType}
            data-formie-phone-input=${isPhoneCountryEnabled(field) ? 'true' : nothing}
            .value=${typeof value === 'string' || typeof value === 'number' ? String(value) : ''}
            ?disabled=${disabled}
            ?readonly=${isCalculationField(field)}
            placeholder=${typeof contract.placeholder === 'string' ? contract.placeholder : ''}
            @input=${(e: Event) => {
                setValue((e.target as HTMLInputElement).value);
            }}
        />
    `;
}

// Lit can't bind an event name held in a constant, so each container gets one listener that calls its latest handler.
const phoneCountryHandlers = new WeakMap<Element, (event: Event) => void>();

function listenForPhoneCountry(handler: (event: Event) => void) {
    return ref((element) => {
        if (!element) {
            return;
        }

        if (!phoneCountryHandlers.has(element)) {
            element.addEventListener(PHONE_COUNTRY_CHANGE_EVENT, (event) => phoneCountryHandlers.get(element)?.(event));
        }

        phoneCountryHandlers.set(element, handler);
    });
}

// Phone answers keep the number and country together. With a country picker, the `formie:phone-country` browser
// module attaches to the marked inputs and reports the country the visitor picks.
function renderPhone(field: ClientFieldDefinition, value: unknown, disabled: boolean, setValue: (v: unknown) => void, labelling: ControlLabelling): TemplateResult {
    const current = phoneFieldValue(value);
    const container = () => document.getElementById(labelling.ids.input)?.closest('.starter-component-phone');
    const input = renderNestedInput(field, current.number, disabled, (number) => {
        setValue({ ...readPhoneFieldControls(container(), current), number: String(number ?? '') });
    }, labelling, 'phone');

    if (!isPhoneCountryEnabled(field)) {
        return input;
    }

    const onCountryChange = (event: Event) => {
        setValue(readPhoneFieldControls(event.currentTarget as Element | null, current));
    };

    // The picker wraps the number input, so it gets a container of its own that Lit never adds children to.
    return html`
        <div class="starter-component-phone" ${listenForPhoneCountry(onCountryChange)}>
            ${input}
            <input type="hidden" data-formie-phone-country-input="true" .value=${current.country ?? ''} />
        </div>
    `;
}

// A Likert scale: one radio per column in each row. With multiple rows the answer is `{ row: column }`.
function renderSurveyLikert(field: ClientFieldDefinition, value: unknown, disabled: boolean, setValue: (v: unknown) => void, labelling: ControlLabelling, errorId: string): TemplateResult {
    const columns = fieldOptions(field);
    const rows = surveyLikertRows(field);
    const multiple = rows.length > 0;
    const answers = multiple && value && typeof value === 'object' ? value as Record<string, unknown> : {};
    const base = labelling.ids.input;
    const naming = groupNaming(field, labelling);
    const hasErrors = labelling.errors.length > 0;

    return html`
        <div class="starter-component-likert" role="group" aria-labelledby=${naming.labelledBy} aria-label=${naming.label} aria-describedby=${naming.describedBy}>
            <table>
                <thead><tr><td></td>${columns.map((column, index) => html`<th id=${`${base}-col-${index}`} scope="col">${column.label}</th>`)}</tr></thead>
                <tbody>
                    ${(multiple ? rows : [{ label: '', value: '' }]).map((row, rowIndex) => {
                        const current = multiple ? String(answers[row.value] ?? '') : (value == null ? '' : String(value));
                        const rowId = `${base}-row-${rowIndex}`;

                        return html`<tr>
                            ${multiple ? html`<th id=${rowId} scope="row">${row.label}</th>` : html`<td></td>`}
                            ${columns.map((column, index) => html`<td><input
                                type="radio"
                                name=${`${base}-${rowIndex}`}
                                value=${column.value}
                                .checked=${current === column.value}
                                ?disabled=${disabled || column.disabled}
                                aria-labelledby=${multiple ? `${base}-col-${index} ${rowId}` : `${base}-col-${index}`}
                                aria-invalid=${hasErrors ? 'true' : nothing}
                                aria-errormessage=${hasErrors ? errorId : nothing}
                                @change=${() => setValue(multiple ? { ...answers, [row.value]: column.value } : column.value)}
                            /></td>`)}
                        </tr>`;
                    })}
                </tbody>
            </table>
        </div>
    `;
}

// Lit can't hand a callback to code that outlives one render, so each list gets one drag listener that calls its
// latest handler.
const rankHandlers = new WeakMap<Element, (from: number, to: number) => void>();

function listenForRankDrag(handler: (from: number, to: number) => void) {
    return ref((element) => {
        if (!element) {
            return;
        }

        if (!rankHandlers.has(element)) {
            attachPointerReorder(element as HTMLElement, 'li', (from, to) => rankHandlers.get(element)?.(from, to));
        }

        rankHandlers.set(element, handler);
    });
}

// A Rank answer lists every option in order. Items reorder by dragging or with their move buttons, through state.
function renderSurveyRank(ctx: RenderViewContext, field: ClientFieldDefinition, value: unknown, disabled: boolean, setValue: (v: unknown) => void, labelling: ControlLabelling): TemplateResult {
    const definition = ctx.state.definition;
    const labels = Object.fromEntries(fieldOptions(field).map((option) => [option.value, option.label]));
    const order = surveyRankOrder(field, value);
    const naming = groupNaming(field, labelling);
    const move = (from: number, to: number) => setValue(moveListItem(order, from, to));

    return html`
        <ol class="starter-component-rank" role="group" aria-labelledby=${naming.labelledBy} aria-label=${naming.label} aria-describedby=${naming.describedBy} ${listenForRankDrag(move)}>
            ${repeat(order, (item) => item, (item, index) => html`<li>
                <span>${labels[item] ?? item}</span>
                <button type="button" ?disabled=${disabled || index === 0} aria-label=${`${getClientString(definition, 'moveUp')}: ${labels[item] ?? item}`} @click=${() => move(index, index - 1)}>${getClientString(definition, 'moveUp')}</button>
                <button type="button" ?disabled=${disabled || index === order.length - 1} aria-label=${`${getClientString(definition, 'moveDown')}: ${labels[item] ?? item}`} @click=${() => move(index, index + 1)}>${getClientString(definition, 'moveDown')}</button>
            </li>`)}
        </ol>
    `;
}

// A star rating: radios shown as stars, filled up to the answer. Choosing the current star again clears it, as in
// server-rendered forms.
function renderSurveyRating(field: ClientFieldDefinition, value: unknown, disabled: boolean, setValue: (v: unknown) => void, labelling: ControlLabelling, errorId: string): TemplateResult {
    const options = fieldOptions(field);
    const current = value == null ? '' : String(value);
    const answered = options.findIndex((option) => option.value === current);
    const color = typeof field.input.starColor === 'string' && field.input.starColor ? `color: ${field.input.starColor}` : nothing;
    const naming = groupNaming(field, labelling);
    const hasErrors = labelling.errors.length > 0;

    return html`
        <div class="starter-component-rating" style=${color} role="radiogroup" aria-labelledby=${naming.labelledBy} aria-label=${naming.label} aria-describedby=${naming.describedBy}>
            ${options.map((option, index) => html`<label style="position: relative; display: inline-block" data-formie-rating-filled=${index <= answered ? 'true' : nothing}>
                <input
                    type="radio"
                    name=${labelling.ids.input}
                    value=${option.value}
                    .checked=${current === option.value}
                    ?disabled=${disabled || option.disabled}
                    style="position: absolute; inset: 0; width: 100%; height: 100%; margin: 0; opacity: 0; cursor: pointer"
                    aria-invalid=${hasErrors ? 'true' : nothing}
                    aria-errormessage=${hasErrors ? errorId : nothing}
                    @change=${() => setValue(option.value)}
                    @click=${() => {
                        if (current === option.value) setValue(null);
                    }}
                />
                <span aria-hidden="true">${index <= answered ? '★' : '☆'}</span>
                <span style=${VISUALLY_HIDDEN_CSS}>${option.label}</span>
            </label>`)}
        </div>
    `;
}

function renderTableCell(column: ClientTableColumn, value: unknown, disabled: boolean, setValue: (v: unknown) => void): TemplateResult {
    if (column.type === 'heading') {
        return html`<span>${typeof value === 'string' ? value : ''}</span>`;
    }

    if (column.type === 'checkbox') {
        return html`<input type="checkbox" aria-label=${column.heading} .checked=${value === true || value === '1' || value === 1} ?disabled=${disabled} @change=${(e: Event) => setValue((e.target as HTMLInputElement).checked)} />`;
    }

    if (column.type === 'multiline') {
        return html`<textarea aria-label=${column.heading} .value=${typeof value === 'string' ? value : ''} ?disabled=${disabled} @input=${(e: Event) => setValue((e.target as HTMLTextAreaElement).value)}></textarea>`;
    }

    if (column.type === 'select' || column.type === 'dropdown') {
        const current = value == null ? '' : String(value);

        // An answer that isn't one of the options shows as empty rather than as the first option.
        return html`<select aria-label=${column.heading} ?disabled=${disabled} @change=${(e: Event) => setValue((e.target as HTMLSelectElement).value)}>
            ${column.options.some((option) => option.value === current) ? nothing : html`<option value=${current} disabled hidden .selected=${true}></option>`}
            ${column.options.map((option) => html`<option value=${option.value} .selected=${option.value === current}>${option.label}</option>`)}
        </select>`;
    }

    const inputTypes: Record<string, string> = { number: 'number', email: 'email', url: 'url', date: 'date', time: 'time', color: 'color' };

    return html`<input type=${inputTypes[column.type] ?? 'text'} aria-label=${column.heading} .value=${typeof value === 'string' || typeof value === 'number' ? String(value) : ''} ?disabled=${disabled} @input=${(e: Event) => setValue((e.target as HTMLInputElement).value)} />`;
}

function renderTable(ctx: RenderViewContext, field: ClientFieldDefinition, value: unknown, disabled: boolean, setValue: (v: unknown) => void, labelling: ControlLabelling): TemplateResult {
    const columns = tableColumns(field);
    const rows = Array.isArray(value) ? (value as Array<Record<string, unknown>>) : [];
    const contract = field.input;
    const fixedRows = contract.static === true || contract.staticRows === true;
    const minRows = Number(contract.minRows ?? 0) || 0;
    const maxRows = Number(contract.maxRows ?? 0) || 0;
    const canAdd = !disabled && !fixedRows && (maxRows <= 0 || rows.length < maxRows);
    const definition = ctx.state.definition;
    const setCell = (rowIndex: number, handle: string, next: unknown) => {
        setValue(rows.map((row, index) => index === rowIndex ? { ...row, [handle]: next } : row));
    };

    return html`
        <div class="grid gap-2" role="group" aria-labelledby=${labelling.labelledByWrapper ? labelling.ids.label : nothing} aria-label=${ownName(field, labelling)}>
            <table>
                <thead>
                    <tr>
                        ${columns.map((column) => html`<th scope="col">${column.heading}</th>`)}
                        ${fixedRows ? nothing : html`<td></td>`}
                    </tr>
                </thead>
                <tbody>
                    ${rows.map((row, rowIndex) => html`
                        <tr>
                            ${columns.map((column) => html`<td>${renderTableCell(column, row[column.handle], disabled, (next) => setCell(rowIndex, column.handle, next))}</td>`)}
                            ${fixedRows ? nothing : html`<td>
                                <button type="button" aria-label=${`${getClientString(definition, 'removeRow')} ${rowIndex + 1}`} ?disabled=${disabled || rows.length <= minRows} @click=${() => setValue(rows.filter((_, index) => index !== rowIndex))}>
                                    ${getClientString(definition, 'remove')}
                                </button>
                            </td>`}
                        </tr>
                    `)}
                </tbody>
            </table>
            ${fixedRows ? nothing : html`<button type="button" ?disabled=${!canAdd} @click=${() => setValue([...rows, createTableRowValue(field)])}>
                ${String(contract.addRowLabel || getClientString(definition, 'addRow'))}
            </button>`}
        </div>
    `;
}

function renderDefaultControl(ctx: RenderViewContext, props: FieldNodeProps & { ids: ClientFieldElementIds; labelledByWrapper: boolean }): TemplateResult {
    const { field, value, errorKey, disabled, setValue } = props;
    const contract = field.input;
    const rendererType = resolveFieldRendererType(field);
    const labelling: ControlLabelling = {
        errors: props.errors,
        ids: props.ids,
        labelledByWrapper: props.labelledByWrapper && Boolean(field.label),
    };
    const groupLabelledBy = labelling.labelledByWrapper ? props.ids.label : nothing;
    const groupDescribedBy = joinIdReferences(instructionsReference(field, labelling)) ?? nothing;

    // Some auto-complete providers replace this input with their own element, so it's HTML Lit leaves alone.
    if (isAddressAutocompletePart(field)) {
        return html`<div class="starter-component-address-autocomplete"><div>${unsafeHTML(addressAutocompleteMarkup(field, {
            id: labelling.ids.input,
            'aria-label': labelling.labelledByWrapper ? undefined : field.label || field.handle,
            'aria-required': field.required ? 'true' : undefined,
            placeholder: typeof contract.placeholder === 'string' ? contract.placeholder : undefined,
        }))}</div></div>`;
    }

    // The range picker fills a start and an end input and fires `input`, when the wrapper reads both. Its markup only
    // depends on the definition, so Lit leaves it alone.
    if (isDateRangePickerField(field)) {
        return html`
            <div class="starter-component-date-range-picker" @input=${(e: Event) => setValue(dateRangePickerValue(e.currentTarget as HTMLElement))}>
                <div>${unsafeHTML(dateRangePickerMarkup(field, {
                    id: labelling.ids.input,
                    'aria-label': labelling.labelledByWrapper ? undefined : field.label || field.handle,
                    'aria-required': field.required ? 'true' : undefined,
                    placeholder: typeof contract.placeholder === 'string' ? contract.placeholder : undefined,
                }))}</div>
            </div>
        `;
    }

    // The date picker turns its input hidden and adds its own visible input next to it, so the input gets a container
    // of its own. It fires `input` with the picked date and time, which the wrapper stores as the answer.
    if (isDatePickerField(field)) {
        return html`
            <div class="starter-component-date-picker" @input=${(e: Event) => {
                // The picker moves the field's attributes to its visible input. The value to submit stays in the
                // original input, which it turns hidden.
                const input = (e.currentTarget as HTMLElement).querySelector('input[type="hidden"]');
                if (input instanceof HTMLInputElement) setValue(datePickerValue(input.value));
            }}>
                <div><input
                    type="text"
                    id=${labelling.ids.input}
                    data-formie-date-datepicker-input
                    aria-label=${ownName(field, labelling)}
                    aria-required=${field.required ? 'true' : nothing}
                    aria-describedby=${describedBy(field, labelling)}
                    .value=${datePickerInputValue(value)}
                    ?disabled=${disabled}
                    placeholder=${typeof contract.placeholder === 'string' ? contract.placeholder : ''}
                /></div>
            </div>
        `;
    }

    if (isCompositeField(field)) {
        return renderComposite(ctx, props, labelling);
    }

    if (isRepeatableField(field)) {
        return renderRepeater(ctx, props, labelling);
    }

    if (rendererType === 'table') {
        return renderTable(ctx, field, value, disabled, setValue, labelling);
    }

    if (rendererType === 'phone') {
        return renderPhone(field, value, disabled, setValue, labelling);
    }

    if (rendererType === 'survey-likert') {
        return renderSurveyLikert(field, value, disabled, setValue, labelling, labelling.ids.errors);
    }

    if (rendererType === 'survey-rank') {
        return renderSurveyRank(ctx, field, value, disabled, setValue, labelling);
    }

    if (rendererType === 'survey-rating') {
        return renderSurveyRating(field, value, disabled, setValue, labelling, labelling.ids.errors);
    }

    if (rendererType === 'section') {
        return html`<hr class="starter-component-section" />`;
    }

    const uploadManager = uploadManagerMarkup(field);

    // The Upload Manager owns this markup, which Lit leaves alone while it stays the same. It updates its status input
    // after each upload or removal and fires `input`, when the wrapper reads the finished uploads from its hidden inputs.
    if (uploadManager !== null) {
        return html`<div class="starter-component-upload-manager" data-formie-upload-field-handle=${fieldContentPath(ctx.state.definition, errorKey)} @input=${(e: Event) => setValue(readUploadManagerEntries(e.currentTarget as HTMLElement))}>
            <div>${unsafeHTML(uploadManager)}</div>
        </div>`;
    }

    if (isFileField(field)) {
        return renderFile(field, value, disabled, setValue, labelling);
    }

    if (rendererType === 'signature') {
        return html`<formie-internal-signature
            aria-invalid=${props.errors.length > 0 ? 'true' : nothing}
            aria-errormessage=${props.errors.length > 0 ? props.errorId : nothing}
            aria-describedby=${props.errors.length > 0 ? props.errorId : nothing}
            .clearLabel=${getClientString(ctx.state.definition, 'clear')}
            .clearAccessibleLabel=${getClientString(ctx.state.definition, 'clearSignature')}
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
        return renderNestedInput(field, value, disabled, setValue, labelling, rendererType);
    }

    if (rendererType === 'radio') {
        const options = fieldOptions(field);
        // Element fields shown as radios keep a list of one ID.
        const listValue = isArrayValuedField(field);
        const current = Array.isArray(value) ? String(value[0] ?? '') : (value == null ? '' : String(value));

        return html`
            <div class="flex flex-col gap-2" role="radiogroup" aria-labelledby=${groupLabelledBy} aria-label=${ownName(field, labelling)} aria-required=${field.required ? 'true' : nothing} aria-describedby=${groupDescribedBy}>
                ${options.map((option) => {
                    const optionValue = option.value;
                    const optionDisabled = disabled || option.disabled;

                    return html`
                        <label class="flex items-center gap-2 text-sm text-slate-800">
                            <input
                                type="radio"
                                aria-invalid=${props.errors.length > 0 ? 'true' : nothing}
                                aria-errormessage=${props.errors.length > 0 ? props.errorId : nothing}
                                aria-describedby=${props.errors.length > 0 ? props.errorId : nothing}
                                name=${props.ids.input}
                                value=${optionValue}
                                .checked=${current === optionValue}
                                ?disabled=${optionDisabled}
                                @change=${() => {
                                    setValue(listValue ? [optionValue] : optionValue);
                                }}
                            />
                            <span>${option.label}</span>
                        </label>
                    `;
                })}
            </div>
        `;
    }

    if (rendererType === 'checkboxes') {
        const options = fieldOptions(field);
        const selected = Array.isArray(value) ? value.map((x) => String(x)) : [];
        const toggle = checkboxToggle(field);
        // "Select all" isn't an answer of its own, so it has no value and isn't validated.
        const toggleControl = toggle ? html`
            <label class="starter-component-checkbox-toggle flex items-center gap-2 text-sm text-slate-800">
                <input type="checkbox" .checked=${allOptionsSelected(field, selected)} ?disabled=${disabled} @change=${(e: Event) => setValue(toggleAllOptions(field, selected, (e.target as HTMLInputElement).checked))} />
                <span>${toggle.label}</span>
            </label>
        ` : nothing;

        return html`
            <div class="flex flex-col gap-2" role="group" aria-labelledby=${groupLabelledBy} aria-label=${ownName(field, labelling)} aria-describedby=${groupDescribedBy}>
                ${toggle?.position === 'top' ? toggleControl : nothing}
                ${options.map((option) => {
                    const optionValue = option.value;
                    const checked = selected.includes(optionValue);
                    const optionDisabled = disabled || option.disabled;

                    return html`
                        <label class="flex items-center gap-2 text-sm text-slate-800">
                            <input
                                type="checkbox"
                                aria-invalid=${props.errors.length > 0 ? 'true' : nothing}
                                aria-errormessage=${props.errors.length > 0 ? props.errorId : nothing}
                                aria-describedby=${props.errors.length > 0 ? props.errorId : nothing}
                                .checked=${checked}
                                ?disabled=${optionDisabled}
                                @change=${() => {
                                    const next = checked
                                        ? selected.filter((x) => x !== optionValue)
                                        : [...selected, optionValue];

                                    setValue(next);
                                }}
                            />
                            <span>${option.label}</span>
                        </label>
                    `;
                })}
                ${toggle?.position === 'bottom' ? toggleControl : nothing}
            </div>
        `;
    }

    if (rendererType === 'agree') {
        const descriptionHtml = typeof contract.descriptionHtml === 'string' ? contract.descriptionHtml : null;

        // As in server-rendered forms, the field label names the group and the description labels the checkbox.
        return html`
            <div role="group" aria-labelledby=${groupLabelledBy} aria-label=${ownName(field, labelling)} aria-describedby=${groupDescribedBy}>
                <label class="flex items-start gap-2 text-sm text-slate-800">
                    <input
                        type="checkbox"
                        id=${props.ids.input}
                        aria-required=${field.required ? 'true' : nothing}
                        aria-invalid=${props.errors.length > 0 ? 'true' : nothing}
                        aria-errormessage=${props.errors.length > 0 ? props.errorId : nothing}
                        aria-describedby=${props.errors.length > 0 ? props.errorId : nothing}
                        .checked=${value === true}
                        ?disabled=${disabled}
                        @change=${(e: Event) => {
                            setValue((e.target as HTMLInputElement).checked);
                        }}
                    />
                    <span>${descriptionHtml ? unsafeHTML(descriptionHtml) : field.label ?? ''}</span>
                </label>
            </div>
        `;
    }

    const display = displayFieldContent(field);

    if (display) {
        return display.kind === 'heading'
            ? staticHtml`<${unsafeStatic(display.level)} class="starter-component-heading">${display.text}</${unsafeStatic(display.level)}>`
            : html`<div class="starter-component-content">${unsafeHTML(display.html)}</div>`;
    }

    const summary = summaryFieldMarkup(field, ctx.state.lastSubmitResult);

    // The summary module replaces the markup inside this container with the server's summary. Lit leaves it alone
    // while the HTML string, which only changes with the token, stays the same.
    if (summary !== null) {
        return html`<div class="starter-component-summary">${unsafeHTML(summary)}</div>`;
    }

    const custom = customFieldMarkup(field, labelling.labelledByWrapper && !isFieldLabelHidden(field) ? props.ids.label : null);

    // Custom link and map fields show the server's markup, which their browser module works in. Lit leaves it alone
    // while the HTML string stays the same, and the answer is read from its named inputs as they change.
    if (custom !== null) {
        const read = (e: Event) => setValue(readCustomFieldControls(e.currentTarget as HTMLElement, field));

        return html`<div class="starter-component-custom-field" @input=${read} @change=${read}>${unsafeHTML(custom)}</div>`;
    }

    const payment = paymentFieldMarkup(field);

    // The payment provider owns this markup. Lit leaves it alone while the HTML string stays the same.
    if (payment !== null) {
        return html`<div class="starter-component-payment">${unsafeHTML(payment)}</div>`;
    }

    if (!isKnownClientFieldType(rendererType)) {
        return html`<div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm text-amber-900">
            Unknown field type:
            ${String(field.meta?.fieldType ?? field.type)}
        </div>`;
    }

    return renderNestedInput(field, value, disabled, setValue, labelling, rendererType);
}

function renderFile(
    field: ClientFieldDefinition,
    value: unknown,
    disabled: boolean,
    setValue: (v: unknown) => void,
    labelling: ControlLabelling,
): TemplateResult {
    const errors = labelling.errors;
    const errorId = labelling.ids.errors;
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
                id=${labelling.ids.input}
                aria-label=${ownName(field, labelling)}
                aria-required=${field.required ? 'true' : nothing}
                aria-invalid=${errors.length > 0 ? 'true' : nothing}
                aria-errormessage=${errors.length > 0 ? errorId : nothing}
                aria-describedby=${describedBy(field, labelling)}
                class="starter-component-control"
                ${addressMarker(field)}
                ?disabled=${disabled}
                ?multiple=${multiple}
                @change=${(e: Event) => {
                    const t = e.target as HTMLInputElement;

                    setValue(Array.from(t.files || []));
                }}
            />
            ${items.length > 0
                ? html`<ul class="starter-component-file-list grid gap-1 text-sm text-slate-600">
                      ${items.map((item) => html`<li>${item}</li>`)}
                  </ul>`
                : nothing}
        </div>
    `;
}

function groupNaming(field: ClientFieldDefinition, labelling: ControlLabelling) {
    return {
        labelledBy: labelling.labelledByWrapper ? labelling.ids.label : nothing,
        label: ownName(field, labelling),
        describedBy: joinIdReferences(instructionsReference(field, labelling)) ?? nothing,
    };
}

function renderComposite(ctx: RenderViewContext, props: FieldNodeProps, labelling: ControlLabelling): TemplateResult {
    const { field, value, errorKey, disabled, setValue } = props;
    const parts = compositePartDefinitions(field);
    const current = value && typeof value === 'object' ? (value as Record<string, unknown>) : {};

    if (parts.length === 0) {
        return html`<div class="text-sm text-amber-800">Composite field has no parts.</div>`;
    }

    const naming = groupNaming(field, labelling);

    return html`
        <div class="starter-component-name-grid" role="group" aria-labelledby=${naming.labelledBy} aria-label=${naming.label} aria-describedby=${naming.describedBy} @input=${(e: Event) => {
            // Auto-complete providers fill the parts and fire `input` and `change`, so the parts are read back together.
            // This runs after a part's own handler, so the full read is the one that sticks.
            if (isAddressField(field)) setValue({ ...current, ...readAddressParts(e.currentTarget as HTMLElement, field) });
        }} @change=${(e: Event) => {
            if (isAddressField(field)) setValue({ ...current, ...readAddressParts(e.currentTarget as HTMLElement, field) });
        }}>
            ${parts
                .filter((part) => part.meta?.hidden !== true)
                .map((definitionPart) => {
                    const partErrorKey = `${errorKey}.${definitionPart.handle}`;
                    // State / Province can follow the chosen country with its subdivisions, label and visibility.
                    const statePart = addressStatePart(definitionPart, current.country, current[definitionPart.handle]);
                    const part = statePart.field;

                    if (statePart.loading) {
                        void loadAddressSubdivisions(definitionPart, String(current.country), ctx.instance.getBrowserRequestOptions()).then(() => ctx.host.requestUpdate());
                    } else if (isDynamicAddressStatePart(definitionPart) && (current[part.handle] ?? '') !== statePart.value) {
                        queueMicrotask(() => setValue({ ...current, [part.handle]: statePart.value }));
                    }

                    if (statePart.hidden) {
                        return nothing;
                    }

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
                        // Group parts are ordinary fields, so they keep the full wrapper with instructions.
                        fieldValueStructure(field) === 'container-parent' ? 'default' : 'compositePart',
                    );
                })}
        </div>
    `;
}

function renderRepeater(ctx: RenderViewContext, props: FieldNodeProps, labelling: ControlLabelling): TemplateResult {
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

    const naming = groupNaming(field, labelling);
    const definition = ctx.state.definition;

    return html`
        <div class="grid gap-4" data-formie-repeater-container role="group" aria-labelledby=${naming.labelledBy} aria-label=${naming.label} aria-describedby=${naming.describedBy}>
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
                            aria-label=${`${getClientString(definition, 'removeRow')} ${rowIndex + 1}`}
                            data-formie-repeater-remove
                            @click=${() => {
                                setValue(currentRows.filter((_, i) => i !== rowIndex));
                            }}
                        >
                            ${getClientString(definition, 'remove')}
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
                ${String(contract.addRowLabel || getClientString(definition, 'addRow'))}
            </button>
        </div>
    `;
}

type FieldNodeProps = {
    field: ClientFieldDefinition;
    value: unknown;
    errors: string[];
    errorKey: string;
    errorId?: string;
    errorAriaLive?: ClientErrorAriaLive;
    disabled: boolean;
    setValue(v: unknown): void;
};

function renderFieldNode(
    ctx: RenderViewContext,
    props: FieldNodeProps,
    fieldLayout: 'default' | 'compositePart' = 'default',
): TemplateResult {
    const { field, value, errors, errorKey, disabled, setValue } = props;
    const fieldState = ctx.state.fieldStates[errorKey];
    const hidden = fieldState?.hidden === true;

    if (hidden) {
        return html``;
    }

    const rendererType = resolveFieldRendererType(field);
    const ids = getClientFieldIds(ctx.state.session, errorKey);
    const errorId = ids.errors;
    const errorAriaLive = getClientErrorAriaLive(ctx.state.definition);
    const regTag =
        ctx.registry.fieldControls[field.type] || ctx.registry.fieldControls[rendererType] || null;

    const syncSet = (v: unknown) => {
        setValue(v);
        ctx.host.requestUpdate();
    };

    const inner = regTag
        ? renderRegistryControl(ctx, regTag, field, value, errorKey, errors, ids, errorAriaLive, disabled, hidden, syncSet)
        : renderDefaultControl(ctx, {
            ...props,
            errorId,
            errorAriaLive,
            ids,
            labelledByWrapper: fieldLayout === 'compositePart' || !ctx.registry.fieldTag,
            setValue: syncSet,
        });

    const counter = renderTextLimitCounter(textLimitCounter(field, value, ctx.state.definition));

    return wrapField(ctx, field, errors, ids, errorAriaLive, counter === nothing ? inner : html`${inner}${counter}`, fieldLayout);
}

// Text fields with a maximum show how much is left under the control, as server-rendered forms do.
function renderTextLimitCounter(counter: TextLimitCounter | null) {
    if (!counter) return nothing;

    return html`<div class="starter-component-limit" data-formie-limit-text="true"><span class=${counter.over ? 'starter-component-limit-number starter-component-limit-number-error' : 'starter-component-limit-number'}>${counter.count}</span> ${counter.text}</div>`;
}

function renderNestedRow(
    ctx: RenderViewContext,
    row: ClientRowDefinition,
    values: Record<string, unknown>,
    errorPrefix: string,
    disabled: boolean | undefined,
    setFieldValue: (f: ClientFieldDefinition, v: unknown) => void,
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
                    disabled: disabled === true || ctx.state.fieldStates[ek]?.disabled === true,
                    setValue(v) {
                        setFieldValue(field, v);
                    },
                });
            })}
        </div>
    `;
}

function renderTopLevelRow(ctx: RenderViewContext, row: ClientRowDefinition): TemplateResult {
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
                @click=${async (e: Event) => {
                    const form = (e.currentTarget as HTMLElement).closest('form');
                    const previousPageId = ctx.state.currentPageId;
                    await ctx.instance.submit(action.type);
                    await ctx.host.updateComplete;
                    focusClientFormResult(form, previousPageId);
                }}
            >
                ${action.label}
            </button>
        `,
    );

    return html`
        <div class="formie-page-actions">
            ${secondary}
            <button type="submit" ?disabled=${!clientActionAllowed(ctx.state)}>${page.actions.primary.label}</button>
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
    // Formie sanitizes rich completion messages before placing them in the client contract.
    const noticeHtml = ctx.state.lastSubmitResult?.messages.notice;
    const shouldStandaloneErr = !!errorMessage && !formErrors.includes(errorMessage);
    const pageShown = !(ctx.state.lastSubmitResult?.completion?.behavior === 'message' && ctx.state.lastSubmitResult.completion.hideForm);
    const progressPosition = getClientPageProgress(ctx.state.definition, ctx.state.currentPageId)?.position;

    const formInner = html`
        ${formErrors.length > 0
            ? html`<div class="starter-core-msg starter-core-msg-error mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert" tabindex="-1" data-formie-form-message>
                  <ul class="list-inside list-disc">
                      ${formErrors.map((e) => html`<li>${e}</li>`)}
                  </ul>
              </div>`
            : nothing}
        ${noticeHtml
            ? html`<div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800" role="status" tabindex="-1" data-formie-form-message>
                  ${unsafeHTML(noticeHtml)}
              </div>`
            : nothing}
        ${shouldStandaloneErr
            ? html`<div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert" tabindex="-1" data-formie-form-message>${errorMessage}</div>`
            : nothing}
        ${pageShown && progressPosition === 'start' ? renderProgress(ctx) : nothing}
        ${pageShown ? renderFormPage(ctx, page) : nothing}
        ${pageShown && progressPosition === 'end' ? renderProgress(ctx) : nothing}
    `;

    const renderId = ctx.state.session.tokens.render ?? '';

    return html`
        <form
            class=${ctx.formClass || 'starter-component-form starter-core-preview text-slate-900'}
            data-formie-definition=${ctx.state.definition.handle}
            data-formie-render-id=${renderId}
            @submit=${async (e: Event) => {
                e.preventDefault();
                const form = e.currentTarget as HTMLFormElement;
                const previousPageId = ctx.state.currentPageId;
                await ctx.instance.submit();
                await ctx.host.updateComplete;
                focusClientFormResult(form, previousPageId);
            }}
        >
            ${formInner}
        </form>
    `;
}

function renderFormPage(
    ctx: RenderViewContext,
    page: ClientFormDefinition['pages'][number],
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

    const showTitle = showsClientPageTitle(ctx.state.definition) && !!page.label;
    const titleId = `formie-${ctx.state.session.tokens.render || ctx.state.session.id}-page-${page.id}-title`;

    return html`
        <section data-page-id=${page.id} class="starter-core-page space-y-4" tabindex="-1" aria-labelledby=${showTitle ? titleId : nothing} aria-label=${!showTitle && ctx.state.definition.pages.length > 1 && page.label ? page.label : nothing}>
            ${showTitle ? html`<h3 id=${titleId} class="starter-core-page-title">${page.label}</h3>` : nothing}
            ${pageBody}
        </section>
    `;
}

// The form's progress through its pages, shown as the server-rendered progress bar is.
function renderProgress(ctx: RenderViewContext): TemplateResult {
    const progress = getClientPageProgress(ctx.state.definition, ctx.state.currentPageId);

    if (!progress) {
        return html``;
    }

    const value = html`<span class="starter-core-progress-value text-xs font-semibold" aria-hidden="true">${progress.percent}%</span>`;
    const inside = !['left', 'right', 'hidden'].includes(progress.valuePosition);
    // Themes use the state to keep the value inside the bar at 0% and 100%, as in server-rendered forms.
    const state = progress.percent <= 0 ? 'start' : (progress.percent >= 100 ? 'end' : 'middle');

    return html`
        <div class="starter-core-progress mb-4 flex items-center gap-2" data-formie-progress-position=${progress.position} data-formie-progress-value-position=${progress.valuePosition} data-formie-progress-state=${state}>
            ${progress.valuePosition === 'left' ? value : nothing}
            <div class="starter-core-progress-track h-4 flex-1 overflow-hidden rounded-full bg-slate-200" role="progressbar" aria-label=${getClientString(ctx.state.definition, 'progress')} aria-valuenow=${progress.percent} aria-valuemin="0" aria-valuemax="100">
                <div class="starter-core-progress-bar flex h-full items-center justify-center rounded-full bg-slate-900 text-white" style=${`width: ${progress.percent}%`}>${inside ? value : nothing}</div>
            </div>
            ${progress.valuePosition === 'right' ? value : nothing}
        </div>
    `;
}

export function renderLoadingView(message = 'Loading form…'): TemplateResult {
    return html`<div class="mt-3 text-sm text-slate-500">${message}</div>`;
}

export function renderErrorView(message: string): TemplateResult {
    return html`<div class="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">${message}</div>`;
}
