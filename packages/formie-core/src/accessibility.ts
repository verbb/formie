import type { FrontendFormDefinition, FrontendFormSession } from './types';

export type FrontendErrorAriaLive = 'polite' | 'assertive' | 'off';

export function getFrontendErrorAriaLive(definition: FrontendFormDefinition): FrontendErrorAriaLive {
    return definition.settings.validation.errorAriaLive || 'polite';
}

export function getFrontendFieldErrorId(session: FrontendFormSession, errorKey: string): string {
    const renderId = session.tokens.render || session.id;

    return `formie-${renderId}-${errorKey}-errors`;
}
