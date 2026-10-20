import { describe, expect, it, vi } from 'vitest';
import { resolveVariableCategories } from './useVariableCategories';

vi.hoisted(() => {
    globalThis.Craft = {
        t: (_category, message) => {
            return message;
        },
    };
});

const self = {
    _id: 'current-upload',
    type: 'FileUpload',
    reference: 'current-reference',
    handle: '',
    label: '',
};
const sibling = {
    _id: 'other-upload',
    type: 'FileUpload',
    reference: 'other-reference',
    handle: 'otherFiles',
    label: 'Other Files',
};
const values = {
    pages: [{ label: 'Page 1', rows: [{ fields: [self, sibling] }] }],
};
const config = {
    staticGroups: {
        form: [
            { label: 'Form Handle', value: '{form:handle}', types: ['text'] },
        ],
    },
};
const request = {
    groups: ['fieldsVariables', 'form'],
    types: ['text'],
    excludeSelf: true,
};
const getFieldTypeByType = () => {
    return {
        referenceValues: [
            {
                kind: 'primary',
                label: 'Value',
                types: ['text'],
                shape: 'inline',
            },
            {
                kind: 'selector',
                selector: 'filename',
                label: 'Filename',
                types: ['text'],
                shape: 'inline',
            },
        ],
    };
};
const editor = (field) => {
    return {
        getFieldValue: (key) => {
            return field[key];
        },
    };
};

describe('variable picker self-exclusion', () => {
    it('excludes an unnamed draft field and its selectors by editor identity', () => {
        const result = resolveVariableCategories(config, values, request, {
            form: editor(self),
            getFieldTypeByType,
        });

        expect(JSON.stringify(result)).not.toContain('current-reference');
        expect(JSON.stringify(result)).toContain(
            '{field:other-reference:filename}',
        );
        expect(result.form).toHaveLength(1);
    });

    it('keeps other fields when the edited handle temporarily matches theirs', () => {
        const result = resolveVariableCategories(config, values, request, {
            form: editor({ ...self, handle: sibling.handle }),
            getFieldTypeByType,
        });

        expect(JSON.stringify(result)).not.toContain('current-reference');
        expect(JSON.stringify(result)).toContain('other-reference');
    });

    it('preserves explicit identity and legacy handle exclusions', () => {
        const explicit = resolveVariableCategories(
            config,
            values,
            { ...request, excludeSelfFieldId: self._id },
            { getFieldTypeByType },
        );
        const legacy = resolveVariableCategories(config, values, request, {
            form: editor({ handle: sibling.handle }),
            getFieldTypeByType,
        });

        expect(JSON.stringify(explicit)).not.toContain('current-reference');
        expect(JSON.stringify(legacy)).not.toContain('other-reference');
    });

    it('allows the current field in consumers that do not request exclusion', () => {
        const result = resolveVariableCategories(
            config,
            values,
            { ...request, excludeSelf: false },
            { form: editor(self), getFieldTypeByType },
        );

        expect(JSON.stringify(result)).toContain('current-reference');
        expect(JSON.stringify(result)).toContain('other-reference');
    });
});
