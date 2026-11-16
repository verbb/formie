import { describe, expect, it, vi } from 'vitest';
import { resolveVariableCategories } from '@form-builder/hooks/useVariableCategories';
import { precedingConditionValues } from '@form-builder/utils/conditionSources';
import { CONDITION_FIELD_TYPES, buildConditionFieldPicker, buildStoredRuleOptionIndex } from './conditionFieldPicker';
import { findOptionLabelByValue } from './variablePicker';

vi.hoisted(() => {
    globalThis.Craft = {
        t: (_category, message) => {
            return message;
        },
    };
});

const earlier = { _id: 'earlier', type: 'Text', reference: 'earlier-ref', handle: 'earlier', label: 'Earlier' };
const edited = { _id: 'edited', type: 'Text', reference: 'edited-ref', handle: 'edited', label: 'Edited' };
const later = { _id: 'later', type: 'Text', reference: 'later-ref', handle: 'later', label: 'Later' };
const laterName = { _id: 'name', type: 'Name', reference: 'name-ref', handle: 'fullName', label: 'Full Name' };
const values = { pages: [{ label: 'Page 1', rows: [{ fields: [earlier, edited, later, laterName] }] }] };
const request = { shapes: ['inline'], types: ['text'], groupFieldsByPage: true, showFieldHandle: true, groups: ['fieldsVariables'] };
const getFieldTypeByType = (type) => {
    return {
        referenceValues: [
            { kind: 'primary', label: 'Value', types: ['text'], shape: 'inline' },
            ...(type === 'Name' ? [{ kind: 'selector', selector: 'firstName', label: 'First Name', types: ['text'], shape: 'inline' }] : []),
        ],
    };
};

describe('stored condition rule labels', () => {
    it('labels a stored rule pointing at a later field, which new rules cannot select', () => {
        const selectable = resolveVariableCategories({}, precedingConditionValues(values, 'edited'), request, { getFieldTypeByType });
        const index = buildStoredRuleOptionIndex({}, values, request, { getFieldTypeByType });

        expect(findOptionLabelByValue(selectable, '{field:later-ref}')).toBeNull();
        expect(index.labelByValue.get('{field:later-ref}')).toBe('Later (later)');
        expect(index.optionByValue.get('{field:later-ref}').value).toBe('{field:later-ref}');
    });

    it('labels a stored rule pointing at the edited field or a later sub-field', () => {
        const index = buildStoredRuleOptionIndex({}, values, request, { getFieldTypeByType });

        expect(index.labelByValue.get('{field:edited-ref}')).toBe('Edited (edited)');
        expect(index.labelByValue.get('{field:name-ref:firstName}')).toBe('Full Name: First Name');
    });
});

describe('boolean condition sources', () => {
    const agree = { _id: 'agree', type: 'Agree', reference: 'agree-ref', handle: 'agreeTerms', label: 'Agree to terms' };
    const dependent = { _id: 'dependent', type: 'Text', reference: 'dependent-ref', handle: 'dependent', label: 'Dependent' };
    const agreeValues = { pages: [{ label: 'Page 1', rows: [{ fields: [agree, dependent] }] }] };
    const agreeRequest = { ...request, types: CONDITION_FIELD_TYPES };
    const getAgreeFieldType = (type) => {
        return type === 'Agree'
            ? { conditionValueType: 'boolean', conditionOperators: ['=', '!=', 'empty', 'notEmpty'], referenceValues: [{ kind: 'primary', label: 'Value', types: ['boolean'], shape: 'inline' }] }
            : getFieldTypeByType(type);
    };

    it('offers an earlier Agree field to a later field', () => {
        const selectable = resolveVariableCategories({}, precedingConditionValues(agreeValues, 'dependent'), agreeRequest, { getFieldTypeByType: getAgreeFieldType });

        expect(findOptionLabelByValue(selectable, '{field:agree-ref}')).toBe('Agree to terms (agreeTerms)');
    });

    it('compares an Agree field with Checked or Unchecked', () => {
        const { modifyValueColumn } = buildConditionFieldPicker({
            formValues: precedingConditionValues(agreeValues, 'dependent'),
            getFieldTypeByType: getAgreeFieldType,
            t: (message) => { return message; },
        });

        expect(modifyValueColumn({ field: '{field:agree-ref}', condition: '=' }, 'value')).toEqual({
            type: 'select',
            options: [{ label: 'Checked', value: '1' }, { label: 'Unchecked', value: '0' }],
        });
    });
});
