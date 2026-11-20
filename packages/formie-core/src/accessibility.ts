import { displayFieldContent, isCompositeField, isRepeatableField, isDatePickerField } from './schema.js';
import type { ClientFieldDefinition, ClientFormDefinition, ClientFormSession, ClientStringKey } from './types.js';

export type ClientErrorAriaLive = 'polite' | 'assertive' | 'off';

export type ClientFieldElementIds = {
    input: string;
    label: string;
    instructions: string;
    errors: string;
};

/**
 * How a field's label relates to its control: `control` labels one form control with `for`, `group` names a
 * set of controls (choices, or an Agree checkbox whose own label is its description), and `none` labels nothing.
 */
export type ClientFieldLabelTarget = 'control' | 'group' | 'none';

export function getClientErrorAriaLive(definition: ClientFormDefinition): ClientErrorAriaLive {
    return definition.settings.validation.errorAriaLive || 'polite';
}

export function getClientFieldErrorId(session: ClientFormSession, errorKey: string): string {
    return `${clientFieldIdPrefix(session, errorKey)}-errors`;
}

export function getClientFieldIds(session: ClientFormSession, errorKey: string): ClientFieldElementIds {
    const prefix = clientFieldIdPrefix(session, errorKey);

    return {
        input: `${prefix}-input`,
        label: `${prefix}-label`,
        instructions: `${prefix}-instructions`,
        errors: `${prefix}-errors`,
    };
}

/**
 * `rendererType` is the adapter's resolved renderer, which maps custom fields onto the built-in controls.
 */
export function getClientFieldLabelTarget(field: ClientFieldDefinition, rendererType: string): ClientFieldLabelTarget {
    // Payment and Summary fields show markup from the server or a browser module, with no control of their own.
    if (displayFieldContent(field) || rendererType === 'signature' || rendererType === 'section' || field.type === 'payment' || field.type === 'summary') {
        return 'none';
    }

    // A date picker is one control, even though its answer has parts.
    if (isDatePickerField(field)) {
        return 'control';
    }

    // Name, Address, Date, Group, Repeater and Table fields, and Survey scales, name the set of controls they hold.
    if (['radio', 'checkboxes', 'agree', 'table', 'survey-likert', 'survey-rank', 'survey-rating'].includes(rendererType) || isCompositeField(field) || isRepeatableField(field)) {
        return 'group';
    }

    return 'control';
}

/**
 * Joins element IDs for `aria-describedby` and similar attributes, or returns undefined when there are none.
 */
export function joinIdReferences(...ids: Array<string | false | null | undefined>): string | undefined {
    const references = ids.filter((id): id is string => typeof id === 'string' && id !== '');

    return references.length > 0 ? references.join(' ') : undefined;
}

function clientFieldIdPrefix(session: ClientFormSession, errorKey: string): string {
    const renderId = session.tokens.render || session.id;

    return `formie-${renderId}-${errorKey}`;
}

const CLIENT_STRING_FALLBACKS: Record<ClientStringKey, string> = {
    addRow: 'Add another row',
    remove: 'Remove',
    removeRow: 'Remove row',
    clear: 'Clear',
    clearSignature: 'Clear signature',
    progress: 'Progress',
    moveUp: 'Move up',
    moveDown: 'Move down',
};

/**
 * Button text for client-rendered controls, translated by the server for the form's site. Older definitions
 * fall back to English.
 */
export function getClientString(definition: Pick<ClientFormDefinition, 'settings'> | null | undefined, key: ClientStringKey): string {
    return definition?.settings?.strings?.[key] || CLIENT_STRING_FALLBACKS[key];
}

/**
 * A label in the Hidden position still names its control, so it stays in the page for assistive technology.
 */
export function isFieldLabelVisuallyHidden(field: ClientFieldDefinition): boolean {
    return field.input.labelPosition === 'hidden';
}

/** Hides content visually while keeping it available to assistive technology. */
export const VISUALLY_HIDDEN_STYLE = {
    position: 'absolute',
    width: '1px',
    height: '1px',
    padding: '0',
    margin: '-1px',
    overflow: 'hidden',
    clip: 'rect(0, 0, 0, 0)',
    whiteSpace: 'nowrap',
    border: '0',
} as const;

export const VISUALLY_HIDDEN_CSS = 'position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0';

/**
 * Moves focus after a submit, page change or Back: to the first invalid control, then to a form-level
 * message (an error summary or completion notice), then to the new page when the page changed.
 */
export function focusClientFormResult(form: Element | null | undefined, previousPageId?: string | null): void {
    if (!form) {
        return;
    }

    const focusable = 'input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), button:not([disabled])';
    const invalid = form.querySelector<HTMLElement>('[aria-invalid="true"]');
    const message = form.querySelector<HTMLElement>('[data-formie-form-message]');
    const page = form.querySelector<HTMLElement>('[data-page-id]');
    const pageChanged = previousPageId != null && page?.getAttribute('data-page-id') !== previousPageId;
    const target = invalid || message || (pageChanged && page ? (page.hasAttribute('tabindex') ? page : page.querySelector<HTMLElement>(focusable)) : null);

    target?.focus();
}

export type ClientPageProgress = {
    percent: number;
    position: 'start' | 'end';
    valuePosition: string;
};

/**
 * The progress bar for a multi-page form, when the form shows one. As in server-rendered forms, Completion counts
 * the pages before the current one and Page Position includes it.
 */
export function getClientPageProgress(definition: Pick<ClientFormDefinition, 'settings' | 'pages'>, currentPageId: string): ClientPageProgress | null {
    const progress = definition.settings.progress;
    const total = definition.pages.length;

    if (!progress?.display || total < 2) {
        return null;
    }

    const index = Math.max(0, definition.pages.findIndex((page) => page.id === currentPageId));
    const completed = progress.calculation === 'page-position' ? index + 1 : index;

    return {
        percent: Math.round((completed / total) * 100),
        position: progress.position === 'start' ? 'start' : 'end',
        valuePosition: progress.valuePosition || 'inside-center',
    };
}

/**
 * Whether the current page shows its name as a heading, from the form's Display Current Page Title setting. As in
 * server-rendered forms, this applies to single-page forms too.
 */
export function showsClientPageTitle(definition: Pick<ClientFormDefinition, 'settings'>): boolean {
    return definition.settings.displayCurrentPageTitle === true;
}
