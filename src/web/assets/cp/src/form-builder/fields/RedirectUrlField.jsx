import { useEngineField } from '@verbb/plugin-kit-react/forms';
import { useTranslation } from '@verbb/plugin-kit-react/hooks';
import { parseReference } from '@verbb/formie-core';
import { VariablePickerField } from './VariablePickerField';

const variableConfig = {
    shapes: ['inline'],
    types: ['text'],
    groups: ['fieldsVariables', 'staticFormVariables', 'staticGeneralVariables', 'staticSiteVariables'],
};

export function RedirectUrlField({ form, field }) {
    const { value } = useEngineField(form, field.name);
    const t = useTranslation();
    const prefix = typeof value === 'string' ? value.trimStart().match(/^\{[^{}]+\}(?!\})/)?.[0] : null;
    const warning = prefix && parseReference(prefix).isValid
        ? t('This URL starts with a variable. If its value comes from a visitor, they can choose the redirect destination. Only use this when you trust the value.')
        : field.warning;

    return <VariablePickerField form={form} field={{ ...field, variableConfig, warning }} />;
}
