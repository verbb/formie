import { describe, expect, it } from 'vitest';
import { getClientErrorAriaLive, getClientFieldErrorId } from './accessibility';
import type { ClientFormDefinition, ClientFormSession } from './types';

describe('frontend field error accessibility', () => {
    it('defaults to polite announcements for older definitions', () => {
        const definition = {
            settings: { validation: {} },
        } as ClientFormDefinition;

        expect(getClientErrorAriaLive(definition)).toBe('polite');
    });

    it.each(['polite', 'assertive', 'off'] as const)('preserves the %s announcement preference', (errorAriaLive) => {
        const definition = {
            settings: { validation: { errorAriaLive } },
        } as ClientFormDefinition;

        expect(getClientErrorAriaLive(definition)).toBe(errorAriaLive);
    });

    it('creates stable, form-scoped error region ids', () => {
        const session = {
            id: 'session-id',
            tokens: { render: 'render-id' },
        } as ClientFormSession;

        expect(getClientFieldErrorId(session, 'contact.email')).toBe('formie-render-id-contact.email-errors');
    });
});
