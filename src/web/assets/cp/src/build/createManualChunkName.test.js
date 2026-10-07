import { describe, expect, it } from 'vitest';

import { createManualChunkName } from '../../vite.config.js';

describe('createManualChunkName', () => {
    it.each([
        '/workspace/node_modules/@verbb/formie-browser/dist/index.js',
        '/workspace/packages/formie-browser/dist/index.js',
        '/workspace/packages/formie-browser/dist/chunks/date-picker.js',
    ])('keeps Formie Browser modules out of application entry chunks: %s', (id) => {
        expect(createManualChunkName(id)).toBe('formie-browser');
    });
});
