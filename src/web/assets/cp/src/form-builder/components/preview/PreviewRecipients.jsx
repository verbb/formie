import React from 'react';
import { PreviewChoiceList } from './PreviewChoiceList';
import { PreviewInput } from './PreviewInput';
import { PreviewSelect } from './PreviewSelect';

export const PreviewRecipients = ({
    displayType = 'hidden',
    placeholder = '',
    options = [],
    value = '',
    layout = 'vertical',
}) => {
    if (displayType === 'dropdown') {
        return (
            <PreviewSelect
                options={options}
                placeholder={placeholder}
                value={value}
                multiple={false}
                useOptionDefaults
            />
        );
    }

    if (displayType === 'checkboxes') {
        return (
            <PreviewChoiceList
                choiceType="checkbox"
                options={options}
                value={value}
                layout={layout}
                useOptionDefaults
            />
        );
    }

    if (displayType === 'radio') {
        return (
            <PreviewChoiceList
                choiceType="radio"
                options={options}
                value={value}
                layout={layout}
                useOptionDefaults
            />
        );
    }

    return (
        <PreviewInput
            placeholder={placeholder || Craft.t('formie', 'Recipient')}
            value={value || ''}
            wrapperClassName="formie-field-preview-control formie-field-preview-control--hidden"
        />
    );
};
