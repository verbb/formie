import { describe, expect, it } from 'vitest';
import { getClientErrorAriaLive, getClientFieldErrorId, getClientFieldIds, getClientFieldLabelTarget, getClientPageProgress, joinIdReferences, showsClientPageTitle } from './accessibility';
import type { ClientFieldDefinition, ClientFormDefinition, ClientFormSession } from './types';

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

    it('creates matching input, label and instructions ids', () => {
        const session = {
            id: 'session-id',
            tokens: {},
        } as unknown as ClientFormSession;

        expect(getClientFieldIds(session, 'items.0.name')).toEqual({
            input: 'formie-session-id-items.0.name-input',
            label: 'formie-session-id-items.0.name-label',
            instructions: 'formie-session-id-items.0.name-instructions',
            errors: getClientFieldErrorId(session, 'items.0.name'),
        });
    });

    it('labels single controls, names groups of controls and skips fields without one control', () => {
        const field = (type: string, extra: Partial<ClientFieldDefinition> = {}) => ({
            type,
            input: {},
            client: { children: { model: 'none' } },
            ...extra,
        }) as unknown as ClientFieldDefinition;

        expect(getClientFieldLabelTarget(field('single-line-text'), 'single-line-text')).toBe('control');
        expect(getClientFieldLabelTarget(field('dropdown'), 'dropdown')).toBe('control');
        expect(getClientFieldLabelTarget(field('file'), 'file')).toBe('control');
        expect(getClientFieldLabelTarget(field('radio'), 'radio')).toBe('group');
        expect(getClientFieldLabelTarget(field('checkboxes'), 'checkboxes')).toBe('group');
        expect(getClientFieldLabelTarget(field('custom'), 'agree')).toBe('group');
        expect(getClientFieldLabelTarget(field('signature'), 'signature')).toBe('none');
        expect(getClientFieldLabelTarget(field('heading', { input: { headingSize: 'h3' } }), 'heading')).toBe('none');
        expect(getClientFieldLabelTarget(field('name', {
            client: { children: { model: 'fixed-parent' } },
            input: { parts: [{ handle: 'firstName', type: 'single-line-text' }] },
        } as Partial<ClientFieldDefinition>), 'name')).toBe('group');
        expect(getClientFieldLabelTarget(field('group', {
            client: { children: { model: 'container-parent' } },
            input: { parts: [{ handle: 'company', type: 'single-line-text' }] },
        } as Partial<ClientFieldDefinition>), 'group')).toBe('group');
        expect(getClientFieldLabelTarget(field('repeater', { client: { children: { model: 'repeatable-parent' } } } as Partial<ClientFieldDefinition>), 'repeater')).toBe('group');
        expect(getClientFieldLabelTarget(field('table'), 'table')).toBe('group');
        expect(getClientFieldLabelTarget(field('section'), 'section')).toBe('none');
    });

    it('joins only the ids that are present', () => {
        expect(joinIdReferences('a', '', false, null, undefined, 'b')).toBe('a b');
        expect(joinIdReferences(false, undefined)).toBeUndefined();
    });

    it('works out page progress as server-rendered forms do', () => {
        const definition = (progress: Record<string, unknown>, pages = 4) => ({
            settings: { progress: { enabled: true, ...progress } },
            pages: Array.from({ length: pages }, (_, index) => ({ id: String(index + 1) })),
        }) as unknown as ClientFormDefinition;

        expect(getClientPageProgress(definition({ display: false }), '1')).toBeNull();
        expect(getClientPageProgress(definition({ display: true }, 1), '1')).toBeNull();
        expect(getClientPageProgress(definition({ display: true, calculation: 'completion' }), '1')).toEqual({ percent: 0, position: 'end', valuePosition: 'inside-center' });
        expect(getClientPageProgress(definition({ display: true, calculation: 'completion' }), '3')?.percent).toBe(50);
        expect(getClientPageProgress(definition({ display: true, calculation: 'page-position', position: 'start', valuePosition: 'right' }), '2')).toEqual({ percent: 50, position: 'start', valuePosition: 'right' });
    });

    it('shows page titles on forms that ask for them, as server-rendered forms do', () => {
        const definition = (displayCurrentPageTitle: boolean, pages: number) => ({
            settings: { displayCurrentPageTitle },
            pages: Array.from({ length: pages }, (_, index) => ({ id: String(index + 1) })),
        }) as unknown as ClientFormDefinition;

        expect(showsClientPageTitle(definition(true, 2))).toBe(true);
        expect(showsClientPageTitle(definition(false, 2))).toBe(false);
        expect(showsClientPageTitle(definition(true, 1))).toBe(true);
    });
});
