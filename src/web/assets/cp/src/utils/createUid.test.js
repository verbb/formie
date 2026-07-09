import { afterEach, describe, expect, it, vi } from 'vitest';

import { createUid } from './createUid';

describe('createUid', () => {
    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('uses crypto.randomUUID when available', () => {
        vi.spyOn(crypto, 'randomUUID').mockReturnValue('11111111-1111-4111-8111-111111111111');

        expect(createUid()).toBe('11111111-1111-4111-8111-111111111111');
    });

    it('falls back when crypto.randomUUID is unavailable', () => {
        const originalRandomUUID = crypto.randomUUID;

        Object.defineProperty(crypto, 'randomUUID', {
            configurable: true,
            value: undefined,
        });

        const uid = createUid();

        Object.defineProperty(crypto, 'randomUUID', {
            configurable: true,
            value: originalRandomUUID,
        });

        expect(uid).toMatch(/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/);
    });
});
