import React from 'react';
import { normalizeOptions, normalizeSelectedValues } from './previewValueUtils';
import { applyOptionAvailabilityToPreviewOptions } from '@form-builder/utils/optionAvailability';

const renderSelectOption = (option, index) => {
    if (option?.optgroup && Array.isArray(option.options)) {
        return (
            <optgroup key={`group-${index}`} label={option.label}>
                {option.options.map((childOption, childIndex) => {
                    return renderSelectOption(childOption, `${index}-${childIndex}`);
                })}
            </optgroup>
        );
    }

    return (
        <option
            key={index}
            value={option?.value ?? ''}
            disabled={option?.disabled === true}
        >
            {option?.label ?? ''}
        </option>
    );
};

export const PreviewSelect = ({
    options = [],
    placeholder = '',
    value = null,
    multiple = false,
    useOptionDefaults = true,
    showPlaceholderOption = true,
    className = 'formie-field-preview-select',
}) => {
    const normalizedOptions = applyOptionAvailabilityToPreviewOptions(normalizeOptions(options));
    const selectedValues = normalizeSelectedValues(value, normalizedOptions, useOptionDefaults);
    const defaultValue = multiple ? selectedValues : (selectedValues[0] ?? '');

    return (
        <select
            className={className}
            multiple={Boolean(multiple)}
            defaultValue={defaultValue}
        >
            {!multiple && showPlaceholderOption && placeholder && (
                <option value="" disabled>
                    {placeholder}
                </option>
            )}

            {normalizedOptions.map((option, index) => {
                return renderSelectOption(option, index);
            })}
        </select>
    );
};
