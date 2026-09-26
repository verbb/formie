import type { ClientFormDefinition, ClientFormSession } from './types';

export type ClientErrorAriaLive = 'polite' | 'assertive' | 'off';

export function getClientErrorAriaLive(definition: ClientFormDefinition): ClientErrorAriaLive {
    return definition.settings.validation.errorAriaLive || 'polite';
}

export function getClientFieldErrorId(session: ClientFormSession, errorKey: string): string {
    const renderId = session.tokens.render || session.id;

    return `formie-${renderId}-${errorKey}-errors`;
}
