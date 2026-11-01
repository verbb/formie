import { describe, expect, it, vi } from 'vitest';
import { resolveVariableCategories } from '../hooks/useVariableCategories';
import {
    MAPPING_VARIABLE_CONFIG,
    SUMMARY_MAPPING_VARIABLE_CONFIG,
    supportsMappingSummaries,
} from './utils/integrationMappingVariables';
import { collectSelectableValues } from './utils/variablePicker';

vi.hoisted(() => {
    globalThis.Craft = { t: (_category, message) => { return message; } };
});

const pickerConfig = {
    groupAliases: { staticFormVariables: ['form'] },
    staticGroups: {
        form: [
            ...['allFields', 'allContentFields', 'allVisibleFields'].map((name) => {
                return { label: name, value: `{${name}}`, shape: 'block', types: [] };
            }),
            { label: 'Name', value: '{form:name}', types: ['text'], shape: 'inline' },
        ],
    },
};
const values = {
    pages: [{ rows: [{ fields: ['Group', 'Repeater'].map((type) => {
        return {
            type, reference: type, handle: type.toLowerCase(), label: type,
            rows: [{ fields: [{ type: 'Text', reference: `${type}Child`, handle: 'answer', label: 'Answer' }] }],
        };
    }) }] }],
};
const getFieldTypeByType = (type) => {
    return {
        allowPrimaryReference: type === 'Text',
        isParent: type !== 'Text',
        isRepeater: type === 'Repeater',
        referenceValues: type === 'Text' ? [{ kind: 'primary', types: ['text'], shape: 'inline' }] : [{
            kind: 'selector', selector: 'summary', label: 'Formatted Summary',
            types: ['text'], shape: 'block', allowTransforms: false, supportsBrowser: false,
        }],
    };
};

describe('summary destination contracts', () => {
    it('does not add formatted containers to ordinary text pickers without a shape filter', () => {
        const categories = resolveVariableCategories(pickerConfig, values, {
            groups: ['fieldsVariables', 'staticFormVariables'], types: ['text'],
        }, { getFieldTypeByType });
        const selectable = collectSelectableValues(categories);
        expect(selectable.has('{field:Group:summary}')).toBe(false);
        expect(selectable.has('{field:Repeater:summary}')).toBe(false);
        expect(selectable.has('{field:GroupChild}')).toBe(true);
    });

    it('offers complete summaries for a free-text destination', () => {
        const request = supportsMappingSummaries({ acceptsSummaries: true }) ? SUMMARY_MAPPING_VARIABLE_CONFIG : MAPPING_VARIABLE_CONFIG;
        const categories = resolveVariableCategories(pickerConfig, values, request, { getFieldTypeByType });
        const selectable = collectSelectableValues(categories);
        for (const token of ['{allFields}', '{allContentFields}', '{allVisibleFields}', '{field:Group:summary}', '{field:Repeater:summary}']) {
            expect(selectable.has(token)).toBe(true);
        }
        expect(selectable.has('{form:name}')).toBe(true);
        expect(selectable.has('{field:GroupChild}')).toBe(true);
    });

    it.each([undefined, null, false, 'true'])('keeps summaries out of destinations the server did not mark: %s', (acceptsSummaries) => {
        expect(supportsMappingSummaries({ acceptsSummaries })).toBe(false);
        const categories = resolveVariableCategories(pickerConfig, values, MAPPING_VARIABLE_CONFIG, { getFieldTypeByType });
        const selectable = collectSelectableValues(categories);
        for (const token of ['{allFields}', '{allContentFields}', '{allVisibleFields}', '{field:Group:summary}', '{field:Repeater:summary}']) {
            expect(selectable.has(token)).toBe(false);
        }
        expect(selectable.has('{form:name}')).toBe(true);
        expect(selectable.has('{field:GroupChild}')).toBe(true);
    });
});
