import React from 'react';
import { renderToStaticMarkup } from 'react-dom/server';
import { describe, expect, it, vi } from 'vitest';
import { RedirectUrlField } from './RedirectUrlField';

const state = vi.hoisted(() => {return { value: '', picker: null }});
vi.mock('@verbb/plugin-kit-react/forms', () => {return { useEngineField: () => {return { value: state.value }} }});
vi.mock('@verbb/plugin-kit-react/hooks', () => {return { useTranslation: () => {return (text) => {return text}} }});
vi.mock('./VariablePickerField', () => {return {
    VariablePickerField: (props) => {
        state.picker = props;
        return null;
    },
}});

describe('redirect URL authoring', () => {
    it.each([
        ['{field:destination}', true],
        ['  {field:destination}/thanks', true],
        ['{field:destination|https%3A%2F%2Fexample.test}', true],
        ['{site:url}thanks', true],
        ['https://example.test/{field:name}', false],
        ['/thanks?name={field:name}', false],
        ['https://{field:host}/thanks', false],
        ['{{ submission.url }}', false],
        ['{invalid', false],
        ['', false],
    ])('shows an advisory warning for %s: %s', (value, warns) => {
        state.value = value;
        const form = {};
        renderToStaticMarkup(React.createElement(RedirectUrlField, { form, field: { name: 'settings.redirectUrl' } }));
        expect(Boolean(state.picker.field.warning)).toBe(warns);
        expect(state.picker.form).toBe(form);
        expect(state.picker.field.variableConfig.groups).toContain('fieldsVariables');
        expect(state.picker.field).not.toHaveProperty('disabled');
        if (warns) {expect(state.picker.field.warning).toContain('they can choose the redirect destination');}
    });
});
