import { describe, expect, it } from 'vitest';
import { isContentEncryptionControlDisabled } from './ContentEncryptionField.jsx';

describe('content encryption field availability', () => {
    it('disables an off toggle when encryption has not been configured', () => {
        expect(isContentEncryptionControlDisabled(false, { disabledWhenOff: true })).toBe(true);
    });

    it('allows an existing encrypted field to be switched off', () => {
        expect(isContentEncryptionControlDisabled(true, { disabledWhenOff: true })).toBe(false);
    });

    it('honours an unconditional disabled state', () => {
        expect(isContentEncryptionControlDisabled(true, { disabled: true })).toBe(true);
    });
});
