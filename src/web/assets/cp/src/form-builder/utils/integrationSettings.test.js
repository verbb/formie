import { describe, expect, it } from 'vitest';

import { updateIntegrationSettingMapping } from './integrationSettings';

describe('updateIntegrationSettingMapping', () => {
    it('updates nested entity mappings without creating dotted root keys', () => {
        const settings = {
            enabled: true,
            customEntitySettings: {
                event: {
                    enabled: true,
                    fieldMapping: {
                        name: 'Original',
                    },
                },
            },
        };

        const updated = updateIntegrationSettingMapping(
            settings,
            'customEntitySettings.event.fieldMapping',
            'name',
            'Updated',
        );

        expect(updated.customEntitySettings.event.fieldMapping.name).toBe('Updated');
        expect(updated['customEntitySettings.event.fieldMapping']).toBeUndefined();
        expect(settings.customEntitySettings.event.fieldMapping.name).toBe('Original');
    });

    it('uses the current field mapping as a fallback for a missing nested value', () => {
        const updated = updateIntegrationSettingMapping(
            { enabled: true },
            'customEntitySettings.event.fieldMapping',
            'email',
            '{field:email}',
            { name: 'Event name' },
        );

        expect(updated.customEntitySettings.event.fieldMapping).toEqual({
            name: 'Event name',
            email: '{field:email}',
        });
    });
});
