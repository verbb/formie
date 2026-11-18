import { displayFieldContent, isCompositeField, isRepeatableField } from './schema.js';
import type { ClientFieldDefinition, ClientFormDefinition, ClientFormSession } from './types.js';

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
    if (rendererType === 'radio' || rendererType === 'checkboxes' || rendererType === 'agree') {
        return 'group';
    }

    if (isCompositeField(field) || isRepeatableField(field) || displayFieldContent(field) || rendererType === 'signature') {
        return 'none';
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
