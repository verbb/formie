import type { FormieModuleDefinition } from '#contracts/modules';
import { ADDRESS_SELECTORS } from '#modules/address/constants';
import { initFormieCombobox } from '#modules/fields/combobox';
import { dispatchFieldEvent, getModuleFieldContainers } from '#modules/fields/shared';
import { createDebug } from '#utils/debug';

const STATE_DYNAMIC_SELECTOR = '[data-formie-address-state-dynamic]';
const STATE_INPUT_SELECTOR = '[data-formie-address-state-input]';
const AUTOFILL_ANCHOR_SELECTOR = '[data-formie-address-state-autofill-anchor]';
const AUTOFILL_ANIMATION_NAME = 'formie-address-autofill-start';
const COUNTRY_SELECTOR = ADDRESS_SELECTORS.country;
const MODULE_ID = 'address-state';
const DEFAULT_SUBDIVISIONS_ACTION = 'formie/address/subdivisions';
const SELECT_THEME_CLASSES = ['formie-select', 'formie-dropdown-input'] as const;
const AUTOFILL_SWEEP_DELAYS_MS = [0, 100, 300] as const;

const debug = createDebug('fields', 'address-state');

type SubdivisionOption = {
    label: string;
    value: string;
    name?: string;
    short?: string;
};

type SubdivisionsResponse = {
    countryCode?: string;
    administrativeAreaType?: string | null;
    administrativeAreaLabel?: string;
    administrativeAreaUsed?: boolean;
    administrativeAreaRequired?: boolean;
    subdivisions?: SubdivisionOption[];
};

type AddressStateOptions = {
    inputMode?: string;
    hideWhenUnused?: boolean;
    useSearchable?: boolean;
    useDatalist?: boolean;
    optionLabel?: 'name' | 'short';
    optionValue?: 'name' | 'short';
    countryOptionValue?: 'short' | 'full';
    placeholder?: string | null;
    subdivisionsAction?: string;
};

type AddressStateControl = HTMLInputElement | HTMLSelectElement;

type ComboboxSelect = HTMLSelectElement & {
    _formieTomSelect?: {
        getValue: () => string;
        setValue: (value: string, silent?: boolean) => void;
    };
};

type FieldState = {
    addressRoot: HTMLElement;
    stateField: HTMLElement;
    stateControl: AddressStateControl;
    countryControl: HTMLInputElement | HTMLSelectElement | null;
    autofillAnchor: HTMLInputElement | null;
    pendingStateValue: string;
    datalistId: string;
    comboboxCleanup: (() => void) | null;
    skeletonEl: HTMLElement | null;
    fetchingAnnouncementEl: HTMLElement | null;
    autofillSweepTimers: number[];
    countryChangeTimer: number | null;
    lastCountry: string;
    fetchGeneration: number;
    required: boolean;
    lastSubdivisions: SubdivisionOption[];
};

const subdivisionDataCache = new Map<string, SubdivisionsResponse | null>();
const subdivisionInflight = new Map<string, Promise<SubdivisionsResponse | null>>();

function getAddressRoot(field: HTMLElement): HTMLElement | null {
    return field.closest('[data-formie-field-type="address"]')
        || field.closest('[data-formie-address-field-layout]')?.closest('[data-formie-field]')
        || field.closest('[data-formie-field]');
}

function getCountryControl(addressRoot: HTMLElement): HTMLInputElement | HTMLSelectElement | null {
    const el = addressRoot.querySelector(COUNTRY_SELECTOR);

    if (el instanceof HTMLInputElement || el instanceof HTMLSelectElement) {
        return el;
    }

    return null;
}

function getStateControl(field: HTMLElement): AddressStateControl | null {
    const el = field.querySelector(STATE_DYNAMIC_SELECTOR);

    if (el instanceof HTMLInputElement || el instanceof HTMLSelectElement) {
        return el;
    }

    return null;
}

function getFieldLabel(field: HTMLElement): HTMLElement | null {
    return field.querySelector('[data-formie-field-label]');
}

function dispatchControlEvents(control: AddressStateControl): void {
    control.dispatchEvent(new Event('input', { bubbles: true }));
    control.dispatchEvent(new Event('change', { bubbles: true }));
}

function syncComboboxValue(control: AddressStateControl, value: string): void {
    if (!(control instanceof HTMLSelectElement)) {
        return;
    }

    const combobox = (control as ComboboxSelect)._formieTomSelect;

    if (!combobox) {
        return;
    }

    if (combobox.getValue() !== value) {
        combobox.setValue(value, true);
    }
}

function setControlValue(control: AddressStateControl, value: string): void {
    if (control.value !== value) {
        control.value = value;
        dispatchControlEvents(control);
        return;
    }

    syncComboboxValue(control, value);
}

function syncAutofillAnchor(fieldState: FieldState, value: string): void {
    if (!fieldState.autofillAnchor) {
        return;
    }

    fieldState.autofillAnchor.value = value;
}

function stashPendingStateValue(fieldState: FieldState): void {
    const fromAnchor = fieldState.autofillAnchor?.value?.trim() || '';
    const fromControl = fieldState.stateControl.value?.trim() || '';
    const next = fromAnchor || fromControl;

    if (next) {
        fieldState.pendingStateValue = next;
    }
}

function getEffectiveStateValue(fieldState: FieldState): string {
    return fieldState.pendingStateValue?.trim()
        || fieldState.autofillAnchor?.value?.trim()
        || fieldState.stateControl.value?.trim()
        || '';
}

function ensureAutofillAnchor(fieldState: FieldState): HTMLInputElement {
    if (fieldState.autofillAnchor) {
        return fieldState.autofillAnchor;
    }

    const existing = fieldState.addressRoot.querySelector(AUTOFILL_ANCHOR_SELECTOR);

    if (existing instanceof HTMLInputElement) {
        fieldState.autofillAnchor = existing;
    } else {
        const anchor = document.createElement('input');
        anchor.type = 'text';
        anchor.setAttribute('data-formie-address-state-autofill-anchor', 'true');
        anchor.setAttribute('autocomplete', 'address-level1');
        anchor.setAttribute('tabindex', '-1');
        anchor.setAttribute('aria-hidden', 'true');
        anchor.className = 'formie-sr-only';
        fieldState.addressRoot.appendChild(anchor);
        fieldState.autofillAnchor = anchor;
    }

    fieldState.stateControl.setAttribute('autocomplete', 'off');

    if (fieldState.stateControl.value && !fieldState.autofillAnchor.value) {
        fieldState.autofillAnchor.value = fieldState.stateControl.value;
    }

    return fieldState.autofillAnchor;
}

function reconcileAutofillValue(fieldState: FieldState): void {
    stashPendingStateValue(fieldState);

    const pending = getEffectiveStateValue(fieldState);

    if (!pending) {
        return;
    }

    const subdivisions = fieldState.lastSubdivisions;
    const resolved = subdivisions.length > 0
        ? (resolveOptionMatch(pending, subdivisions) || pending)
        : pending;

    setControlValue(fieldState.stateControl, resolved);
    syncComboboxValue(fieldState.stateControl, resolved);
    syncAutofillAnchor(fieldState, resolved);
    fieldState.pendingStateValue = resolved;
}

function buildSubdivisionsUrl(action: string, country: string, optionLabel: string, optionValue: string): string {
    const url = new URL(action.startsWith('/') ? action : `/actions/${action}`, window.location.origin);
    url.searchParams.set('country', country);
    url.searchParams.set('optionLabel', optionLabel);
    url.searchParams.set('optionValue', optionValue);

    return url.toString();
}

function getSubdivisionsCacheKey(
    country: string,
    optionLabel: string,
    optionValue: string,
    action: string,
): string {
    return [country, optionLabel, optionValue, action].join('|');
}

function hasResolvedSubdivisions(cacheKey: string): boolean {
    return subdivisionDataCache.has(cacheKey);
}

async function fetchSubdivisions(
    country: string,
    optionLabel: string,
    optionValue: string,
    action: string,
): Promise<SubdivisionsResponse | null> {
    const cacheKey = getSubdivisionsCacheKey(country, optionLabel, optionValue, action);

    if (subdivisionDataCache.has(cacheKey)) {
        return subdivisionDataCache.get(cacheKey) || null;
    }

    if (!subdivisionInflight.has(cacheKey)) {
        subdivisionInflight.set(cacheKey, (async() => {
            try {
                const response = await fetch(buildSubdivisionsUrl(action, country, optionLabel, optionValue), {
                    headers: {
                        Accept: 'application/json',
                    },
                });

                if (!response.ok) {
                    subdivisionDataCache.set(cacheKey, null);
                    return null;
                }

                const data = await response.json() as SubdivisionsResponse;
                subdivisionDataCache.set(cacheKey, data);

                return data;
            } catch (error) {
                debug.warn('Failed fetching subdivisions.', { country, error });
                subdivisionDataCache.set(cacheKey, null);
                return null;
            } finally {
                subdivisionInflight.delete(cacheKey);
            }
        })());
    }

    return subdivisionInflight.get(cacheKey) || null;
}

function resolveOptionMatch(value: string, subdivisions: SubdivisionOption[]): string | null {
    const normalized = value.trim().toLowerCase();

    if (!normalized) {
        return null;
    }

    for (const option of subdivisions) {
        if (option.value.toLowerCase() === normalized) {
            return option.value;
        }

        if ((option.name || '').toLowerCase() === normalized) {
            return option.value;
        }

        if ((option.short || '').toLowerCase() === normalized) {
            return option.value;
        }

        if (option.label.toLowerCase() === normalized) {
            return option.value;
        }
    }

    return null;
}

function copyControlAttributes(source: AddressStateControl, target: AddressStateControl): void {
    const preserve = [
        'id',
        'name',
        'required',
        'disabled',
        'placeholder',
        'aria-describedby',
        'data-formie-input-id',
        'data-formie-input-type',
        'data-formie-input-error-state',
        'data-formie-address-state-dynamic',
        'data-formie-address-state-hide-when-unused',
        'data-formie-address-state-use-searchable',
        'data-formie-address-state-use-datalist',
        'data-formie-address-state-option-label',
        'data-formie-address-state-option-value',
    ];

    preserve.forEach((attribute) => {
        const value = source.getAttribute(attribute);

        if (value === null) {
            target.removeAttribute(attribute);
            return;
        }

        target.setAttribute(attribute, value);
    });
}

function applyTextThemeClasses(template: AddressStateControl, input: HTMLInputElement): void {
    input.className = template.className;
}

function applySelectThemeClasses(template: AddressStateControl, select: HTMLSelectElement): void {
    const classes: string[] = [...SELECT_THEME_CLASSES];

    if (template.classList.contains('formie-input-error')) {
        classes.push('formie-input-error');
    }

    select.className = classes.join(' ');
}

function replaceControl(
    current: AddressStateControl,
    next: AddressStateControl,
): AddressStateControl {
    current.parentNode?.replaceChild(next, current);
    return next;
}

function createTextControl(template: AddressStateControl, value: string): HTMLInputElement {
    const input = document.createElement('input');
    input.type = 'text';
    copyControlAttributes(template, input);
    applyTextThemeClasses(template, input);
    input.setAttribute('data-formie-input-type', 'text');
    input.setAttribute('data-formie-address-state-input', 'true');
    input.setAttribute('data-formie-single-line-text-input', 'true');
    input.setAttribute('autocomplete', 'off');
    input.removeAttribute('data-formie-combobox-input');
    input.value = value;

    return input;
}

function populateSelectOptions(
    select: HTMLSelectElement,
    subdivisions: SubdivisionOption[],
    placeholder: string | null,
    selectedValue = '',
): void {
    select.innerHTML = '';

    const emptyOption = document.createElement('option');
    emptyOption.value = '';
    emptyOption.textContent = placeholder || '';
    select.appendChild(emptyOption);

    subdivisions.forEach((option) => {
        const optionEl = document.createElement('option');
        optionEl.value = option.value;
        optionEl.textContent = option.label;
        select.appendChild(optionEl);
    });

    select.value = resolveOptionMatch(selectedValue, subdivisions) || selectedValue;
}

function createSelectControl(
    template: AddressStateControl,
    value: string,
    subdivisions: SubdivisionOption[],
    placeholder: string | null,
): HTMLSelectElement {
    const select = document.createElement('select');
    copyControlAttributes(template, select);
    applySelectThemeClasses(template, select);
    select.setAttribute('data-formie-input-type', 'select');
    select.setAttribute('data-formie-address-state-input', 'true');
    select.setAttribute('data-formie-select', 'true');
    select.setAttribute('data-formie-dropdown-input', 'true');
    select.removeAttribute('data-formie-single-line-text-input');
    select.setAttribute('autocomplete', 'off');
    populateSelectOptions(select, subdivisions, placeholder, value);

    return select;
}

function syncDatalist(
    control: HTMLInputElement,
    datalistId: string,
    subdivisions: SubdivisionOption[],
    useDatalist: boolean,
): void {
    const existing = control.list;

    if (!useDatalist || subdivisions.length === 0) {
        control.removeAttribute('list');

        if (existing) {
            existing.remove();
        }

        return;
    }

    let datalist = control.ownerDocument.getElementById(datalistId) as HTMLDataListElement | null;

    if (!datalist) {
        datalist = control.ownerDocument.createElement('datalist');
        datalist.id = datalistId;
        control.insertAdjacentElement('afterend', datalist);
    }

    datalist.innerHTML = '';
    subdivisions.forEach((option) => {
        const optionEl = control.ownerDocument.createElement('option');
        optionEl.value = option.label;
        datalist?.appendChild(optionEl);
    });

    control.setAttribute('list', datalistId);
}

function setFieldVisibility(fieldState: FieldState, visible: boolean): void {
    const { stateField, stateControl, required } = fieldState;

    stateField.classList.toggle('formie-conditionally-hidden', !visible);
    stateField.toggleAttribute('data-formie-conditionally-hidden', !visible);

    if (!visible) {
        stateControl.required = false;
        stateControl.disabled = true;
        return;
    }

    stateControl.disabled = false;
    stateControl.required = required;
}

function ensureFetchingAnnouncement(fieldState: FieldState): HTMLElement {
    if (fieldState.fetchingAnnouncementEl) {
        return fieldState.fetchingAnnouncementEl;
    }

    const announcement = document.createElement('div');
    announcement.className = 'formie-sr-only';
    announcement.setAttribute('data-formie-address-state-fetching-announce', 'true');
    announcement.setAttribute('aria-live', 'polite');
    announcement.setAttribute('aria-atomic', 'true');
    fieldState.addressRoot.appendChild(announcement);
    fieldState.fetchingAnnouncementEl = announcement;

    return announcement;
}

function createStateSkeleton(): HTMLElement {
    const skeleton = document.createElement('div');
    skeleton.className = 'formie-address-state-skeleton';
    skeleton.setAttribute('data-formie-address-state-skeleton', 'true');
    skeleton.setAttribute('aria-hidden', 'true');

    const input = document.createElement('div');
    input.className = 'formie-address-state-skeleton-input';
    skeleton.appendChild(input);

    return skeleton;
}

function showFetchingUI(fieldState: FieldState): void {
    fieldState.addressRoot.setAttribute('data-formie-address-state-fetching', 'true');
    fieldState.countryControl?.setAttribute('aria-busy', 'true');
    fieldState.stateControl.setAttribute('aria-hidden', 'true');
    fieldState.stateControl.setAttribute('tabindex', '-1');

    fieldState.stateField.classList.remove('formie-conditionally-hidden');
    fieldState.stateField.removeAttribute('data-formie-conditionally-hidden');
    fieldState.stateField.setAttribute('data-formie-address-state-skeleton-active', 'true');

    const controlWrapper = fieldState.stateField.querySelector('[data-formie-field-control]');

    if (controlWrapper instanceof HTMLElement) {
        if (!fieldState.skeletonEl) {
            fieldState.skeletonEl = createStateSkeleton();
            controlWrapper.appendChild(fieldState.skeletonEl);
        }

        fieldState.skeletonEl.removeAttribute('hidden');
    }

    ensureFetchingAnnouncement(fieldState).textContent = 'Loading state or province options for the selected country.';
}

function hideFetchingUI(fieldState: FieldState): void {
    fieldState.addressRoot.removeAttribute('data-formie-address-state-fetching');
    fieldState.countryControl?.removeAttribute('aria-busy');
    fieldState.stateControl.removeAttribute('aria-hidden');
    fieldState.stateControl.removeAttribute('tabindex');
    fieldState.stateField.removeAttribute('data-formie-address-state-skeleton-active');
    fieldState.skeletonEl?.setAttribute('hidden', 'hidden');

    if (fieldState.fetchingAnnouncementEl) {
        fieldState.fetchingAnnouncementEl.textContent = '';
    }
}

function updateFieldLabel(stateField: HTMLElement, label: string): void {
    const labelEl = getFieldLabel(stateField);

    if (!labelEl) {
        return;
    }

    const required = labelEl.querySelector('[data-formie-field-required]');
    labelEl.textContent = label;

    if (required) {
        labelEl.appendChild(required);
    }
}

function initSearchableSelect(
    select: HTMLSelectElement,
    placeholder: string | null,
): () => void {
    select.setAttribute('data-formie-combobox-input', 'true');

    dispatchFieldEvent(select, 'combobox', 'before-init', {
        select,
        options: { placeholder },
    });

    const cleanup = initFormieCombobox(
        select as Parameters<typeof initFormieCombobox>[0],
        { placeholder },
    );

    dispatchFieldEvent(select, 'combobox', 'after-init', {
        combobox: (select as Parameters<typeof initFormieCombobox>[0])._formieTomSelect,
        options: { placeholder },
    });

    return cleanup;
}

async function applyCountryState(
    fieldState: FieldState,
    options: AddressStateOptions,
): Promise<void> {
    const {
        hideWhenUnused = true,
        useSearchable = true,
        useDatalist = true,
        optionLabel = 'name',
        optionValue = 'name',
        placeholder = null,
        subdivisionsAction = DEFAULT_SUBDIVISIONS_ACTION,
    } = options;

    const country = fieldState.countryControl?.value?.trim() || '';
    stashPendingStateValue(fieldState);
    const currentValue = getEffectiveStateValue(fieldState);
    const fetchGeneration = ++fieldState.fetchGeneration;

    fieldState.comboboxCleanup?.();
    fieldState.comboboxCleanup = null;

    if (!country) {
        updateFieldLabel(
            fieldState.stateField,
            fieldState.stateField.dataset.formieAddressStateDefaultLabel || 'State / Province',
        );

        hideFetchingUI(fieldState);

        if (hideWhenUnused) {
            setFieldVisibility(fieldState, false);
            setControlValue(fieldState.stateControl, '');
            syncAutofillAnchor(fieldState, '');
            fieldState.pendingStateValue = '';
            fieldState.lastSubdivisions = [];
            return;
        }

        setFieldVisibility(fieldState, true);

        if (fieldState.stateControl instanceof HTMLSelectElement) {
            const textControl = createTextControl(fieldState.stateControl, currentValue);
            fieldState.stateControl = replaceControl(fieldState.stateControl, textControl);
        } else {
            fieldState.stateControl.disabled = true;
            fieldState.stateControl.placeholder = placeholder || fieldState.stateControl.placeholder;
        }

        fieldState.lastSubdivisions = [];
        return;
    }

    const cacheKey = getSubdivisionsCacheKey(country, optionLabel, optionValue, subdivisionsAction);
    const shouldShowFetchingUI = !hasResolvedSubdivisions(cacheKey);

    if (shouldShowFetchingUI) {
        showFetchingUI(fieldState);
    }

    const response = await fetchSubdivisions(country, optionLabel, optionValue, subdivisionsAction);

    if (fetchGeneration !== fieldState.fetchGeneration) {
        return;
    }

    const subdivisions = response?.subdivisions || [];
    const administrativeAreaUsed = response?.administrativeAreaUsed ?? true;
    const administrativeAreaLabel = response?.administrativeAreaLabel || 'State / Province';
    fieldState.lastSubdivisions = subdivisions;

    hideFetchingUI(fieldState);

    if (hideWhenUnused && !administrativeAreaUsed) {
        setFieldVisibility(fieldState, false);
        setControlValue(fieldState.stateControl, '');
        syncAutofillAnchor(fieldState, '');
        fieldState.pendingStateValue = '';
        return;
    }

    setFieldVisibility(fieldState, true);
    updateFieldLabel(fieldState.stateField, administrativeAreaLabel);

    if (subdivisions.length > 0) {
        const select = fieldState.stateControl instanceof HTMLSelectElement
            ? fieldState.stateControl
            : createSelectControl(fieldState.stateControl, currentValue, subdivisions, placeholder);

        if (fieldState.stateControl !== select) {
            fieldState.stateControl = replaceControl(fieldState.stateControl, select);
        } else {
            applySelectThemeClasses(fieldState.stateControl, select);
            populateSelectOptions(select, subdivisions, placeholder, select.value);
        }

        if (useSearchable) {
            fieldState.comboboxCleanup = initSearchableSelect(select, placeholder);
        } else {
            select.removeAttribute('data-formie-combobox-input');
        }

        setControlValue(select, resolveOptionMatch(currentValue, subdivisions) || currentValue);
        reconcileAutofillValue(fieldState);
        return;
    }

    const textControl = fieldState.stateControl instanceof HTMLInputElement
        ? fieldState.stateControl
        : createTextControl(fieldState.stateControl, currentValue);

    if (fieldState.stateControl !== textControl) {
        fieldState.stateControl = replaceControl(fieldState.stateControl, textControl);
    }

    textControl.disabled = false;
    syncDatalist(textControl, fieldState.datalistId, subdivisions, useDatalist);
    setControlValue(textControl, currentValue);
    reconcileAutofillValue(fieldState);
}

function scheduleAutofillSweep(fieldState: FieldState, options: AddressStateOptions): void {
    const sweep = () => {
        stashPendingStateValue(fieldState);

        if (!fieldState.countryControl?.value?.trim()) {
            return;
        }

        fieldState.lastCountry = '';

        void applyCountryState(fieldState, options).then(() => {
            reconcileAutofillValue(fieldState);
        });
    };

    AUTOFILL_SWEEP_DELAYS_MS.forEach((delay) => {
        const timer = window.setTimeout(sweep, delay);
        fieldState.autofillSweepTimers.push(timer);
    });
}

function clearScheduledAutofillTimers(fieldState: FieldState): void {
    if (fieldState.countryChangeTimer !== null) {
        window.clearTimeout(fieldState.countryChangeTimer);
        fieldState.countryChangeTimer = null;
    }

    fieldState.autofillSweepTimers.forEach((timer) => {
        window.clearTimeout(timer);
    });
    fieldState.autofillSweepTimers = [];
}

function initAddressStateField(field: HTMLElement, options: AddressStateOptions): () => void {
    const addressRoot = getAddressRoot(field);

    if (!addressRoot) {
        debug.warn('Address root not found; skipping field.');
        return () => {};
    }

    const stateControl = getStateControl(field);

    if (!stateControl) {
        debug.warn('Dynamic state control not found; skipping field.');
        return () => {};
    }

    const labelEl = getFieldLabel(field);

    if (labelEl && !field.dataset.formieAddressStateDefaultLabel) {
        field.dataset.formieAddressStateDefaultLabel = labelEl.textContent?.trim() || 'State / Province';
    }

    const fieldState: FieldState = {
        addressRoot,
        stateField: field,
        stateControl,
        countryControl: getCountryControl(addressRoot),
        autofillAnchor: null,
        pendingStateValue: stateControl.value?.trim() || '',
        datalistId: `formie-address-state-datalist-${stateControl.getAttribute('data-formie-input-id') || Math.random().toString(36).slice(2)}`,
        comboboxCleanup: null,
        skeletonEl: null,
        fetchingAnnouncementEl: null,
        autofillSweepTimers: [],
        countryChangeTimer: null,
        lastCountry: '',
        fetchGeneration: 0,
        required: stateControl.required,
        lastSubdivisions: [],
    };

    ensureAutofillAnchor(fieldState);

    const refresh = (force = false) => {
        const country = fieldState.countryControl?.value?.trim() || '';

        if (!force && country === fieldState.lastCountry) {
            return;
        }

        const previousCountry = fieldState.lastCountry;
        const previousValue = getEffectiveStateValue(fieldState);
        fieldState.lastCountry = country;

        void applyCountryState(fieldState, options).then(() => {
            reconcileAutofillValue(fieldState);

            if (!previousCountry || !country || previousCountry === country) {
                return;
            }

            const resolved = resolveOptionMatch(previousValue, fieldState.lastSubdivisions);

            if (!resolved && !getEffectiveStateValue(fieldState)) {
                setControlValue(fieldState.stateControl, '');
                syncAutofillAnchor(fieldState, '');
            }
        });
    };

    const onCountryChange = () => {
        if (fieldState.countryChangeTimer !== null) {
            window.clearTimeout(fieldState.countryChangeTimer);
        }

        fieldState.countryChangeTimer = window.setTimeout(() => {
            fieldState.countryChangeTimer = null;
            stashPendingStateValue(fieldState);
            refresh(true);
        }, 50);
    };

    const onAddressInput = (event: Event) => {
        const target = event.target;

        if (!(target instanceof HTMLInputElement || target instanceof HTMLSelectElement)) {
            return;
        }

        const isAnchor = target === fieldState.autofillAnchor;
        const isStateControl = target.matches(STATE_DYNAMIC_SELECTOR)
            || target.matches(STATE_INPUT_SELECTOR);

        if (!isAnchor && !isStateControl) {
            return;
        }

        if (isStateControl && !isAnchor) {
            syncAutofillAnchor(fieldState, target.value);
        }

        stashPendingStateValue(fieldState);

        if (!fieldState.countryControl?.value?.trim()) {
            return;
        }

        if (fieldState.lastSubdivisions.length > 0) {
            reconcileAutofillValue(fieldState);
            return;
        }

        fieldState.lastCountry = '';

        void applyCountryState(fieldState, options).then(() => {
            reconcileAutofillValue(fieldState);
        });
    };

    const onAutofillAnimationStart = (event: AnimationEvent) => {
        if (event.animationName !== AUTOFILL_ANIMATION_NAME) {
            return;
        }

        const target = event.target;

        if (!(target instanceof HTMLInputElement || target instanceof HTMLSelectElement)) {
            return;
        }

        if (!addressRoot.contains(target)) {
            return;
        }

        stashPendingStateValue(fieldState);

        if (!fieldState.countryControl?.value?.trim()) {
            return;
        }

        fieldState.lastCountry = '';

        void applyCountryState(fieldState, options).then(() => {
            reconcileAutofillValue(fieldState);
        });
    };

    const onProviderPopulate = (event: Event) => {
        const detail = (event as CustomEvent).detail as { state?: string } | undefined;
        const stateValue = detail?.state?.trim();

        if (!stateValue) {
            refresh();
            return;
        }

        fieldState.pendingStateValue = stateValue;
        syncAutofillAnchor(fieldState, stateValue);

        void applyCountryState(fieldState, options).then(() => {
            setControlValue(fieldState.stateControl, stateValue);
            syncComboboxValue(fieldState.stateControl, stateValue);
            fieldState.lastCountry = fieldState.countryControl?.value?.trim() || '';
        });
    };

    fieldState.countryControl?.addEventListener('change', onCountryChange);
    fieldState.countryControl?.addEventListener('input', onCountryChange);
    addressRoot.addEventListener('input', onAddressInput);
    addressRoot.addEventListener('change', onAddressInput);
    addressRoot.addEventListener('animationstart', onAutofillAnimationStart);
    addressRoot.addEventListener('formie:address:google:populate', onProviderPopulate);
    addressRoot.addEventListener('formie:address:address-finder:populate', onProviderPopulate);
    addressRoot.addEventListener('formie:address:loqate:populate', onProviderPopulate);
    addressRoot.addEventListener('formie:address:place-kit:populate', onProviderPopulate);

    refresh();
    scheduleAutofillSweep(fieldState, options);

    return () => {
        clearScheduledAutofillTimers(fieldState);
        hideFetchingUI(fieldState);
        fieldState.skeletonEl?.remove();
        fieldState.fetchingAnnouncementEl?.remove();
        fieldState.autofillAnchor?.remove();
        fieldState.comboboxCleanup?.();
        fieldState.countryControl?.removeEventListener('change', onCountryChange);
        fieldState.countryControl?.removeEventListener('input', onCountryChange);
        addressRoot.removeEventListener('input', onAddressInput);
        addressRoot.removeEventListener('change', onAddressInput);
        addressRoot.removeEventListener('animationstart', onAutofillAnimationStart);
        addressRoot.removeEventListener('formie:address:google:populate', onProviderPopulate);
        addressRoot.removeEventListener('formie:address:address-finder:populate', onProviderPopulate);
        addressRoot.removeEventListener('formie:address:loqate:populate', onProviderPopulate);
        addressRoot.removeEventListener('formie:address:place-kit:populate', onProviderPopulate);
    };
}

export const addressStateModule: FormieModuleDefinition = {
    id: MODULE_ID,
    kind: 'field',
    match: (ctx) => {
        return !!ctx.target.querySelector(STATE_DYNAMIC_SELECTOR);
    },
    setup: async (ctx) => {
        const options = (ctx.options || {}) as AddressStateOptions;
        const fields = getModuleFieldContainers(ctx);
        const cleanups = fields.map((field) => initAddressStateField(field, options));

        debug.log('Module setup.', { fieldCount: fields.length });

        return {
            destroy: () => {
                cleanups.forEach((cleanup) => cleanup());
                debug.log('Module destroy.', { fieldCount: fields.length });
            },
        };
    },
};
