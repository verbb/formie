import { describe, expect, it } from 'vitest';
import { getFrontendErrorAriaLive, getFrontendFieldErrorId } from './accessibility';
import type { FrontendFormDefinition, FrontendFormSession } from './types';

describe('frontend field error accessibility', () => {
    it('defaults to polite announcements for older definitions', () => {
        const definition = {
            settings: { validation: {} },
        } as FrontendFormDefinition;

        expect(getFrontendErrorAriaLive(definition)).toBe('polite');
    });

    it.each(['polite', 'assertive', 'off'] as const)('preserves the %s announcement preference', (errorAriaLive) => {
        const definition = {
            settings: { validation: { errorAriaLive } },
        } as FrontendFormDefinition;

        expect(getFrontendErrorAriaLive(definition)).toBe(errorAriaLive);
    });

    it('creates stable, form-scoped error region ids', () => {
        const session = {
            id: 'session-id',
            tokens: { render: 'render-id' },
        } as FrontendFormSession;

        expect(getFrontendFieldErrorId(session, 'contact.email')).toBe('formie-render-id-contact.email-errors');
    });
});
