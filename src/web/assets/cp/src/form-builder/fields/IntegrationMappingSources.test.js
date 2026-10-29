import { describe, expect, it, vi } from 'vitest';
import { MAPPING_VARIABLE_CONFIG } from './IntegrationFieldMappingField';
import { resolveVariableCategories } from '../hooks/useVariableCategories';
import { collectSelectableValues } from './utils/variablePicker';

vi.hoisted(() => {
    globalThis.Craft = { t: (_category, message) => { return message; } };
});

vi.mock('@verbb/plugin-kit-react/components', () => { return {}; });
vi.mock('@verbb/plugin-kit-react/forms', () => { return {}; });
vi.mock('@verbb/plugin-kit-react/utils', () => { return { cn: () => { return ''; } }; });

vi.mock('@form-builder/fields/components/FormBuilderVariablePickerControl', () => { return {}; });
vi.mock('@form-builder/fields/variable-picker/VariablePickerInputCell', () => { return {}; });
vi.mock('@form-builder/fields/components/VariablePickerActionsMenu', () => { return {}; });

describe('integration mapping sources', () => {
    it('offers Agree alongside text, date and number fields without allowing block values', () => {
        const definitions = {
            Agree: { types: ['boolean'], shape: 'inline' },
            Text: { types: ['text'], shape: 'inline' },
            Date: { types: ['date'], shape: 'inline' },
            Number: { types: ['number'], shape: 'inline' },
            Content: { types: ['text'], shape: 'block' },
        };
        const fields = Object.keys(definitions).map((type) => {
            return { type, handle: type, reference: type, label: type };
        });
        const categories = resolveVariableCategories({}, {
            pages: [{ label: 'Page 1', rows: [{ fields }] }],
        }, MAPPING_VARIABLE_CONFIG, {
            getFieldTypeByType: (type) => {
                return { referenceValues: [{ kind: 'primary', ...definitions[type] }] };
            },
        });
        expect([...collectSelectableValues(categories)].filter(Boolean)).toEqual([
            '{field:Agree}', '{field:Text}', '{field:Date}', '{field:Number}',
        ]);
    });
});
