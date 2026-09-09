import { describe, expect, it } from 'vitest';
import { buildActionUrl } from './rest';

describe('buildActionUrl', () => {
    it('preserves Craft subdirectory install paths for absolute bases', () => {
        expect(buildActionUrl('https://example.test/craft/', '/actions/formie/client/forms/load'))
            .toBe('https://example.test/craft/actions/formie/client/forms/load');
    });

    it('joins relative subdirectory bases', () => {
        expect(buildActionUrl('/craft', '/actions/formie/client/forms/load'))
            .toBe('/craft/actions/formie/client/forms/load');
    });

    it('uses root-relative actions when the base is the site root', () => {
        expect(buildActionUrl('https://example.test/', '/actions/formie/client/forms/load'))
            .toBe('https://example.test/actions/formie/client/forms/load');
    });
});
