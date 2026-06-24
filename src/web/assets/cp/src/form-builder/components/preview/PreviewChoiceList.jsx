import React from 'react';

import { cn } from '@verbb/plugin-kit-react/utils';

import { normalizeOptions, normalizeSelectedValues } from './previewValueUtils';
import {
    applyOptionAvailabilityToPreviewOptions,
    isOptionFrontEndDisabled,
} from '@form-builder/utils/optionAvailability';

const OTHER_OPTION_VALUE = '__other__';

export const PreviewChoiceList = ({
    choiceType = 'checkbox',
    options = [],
    value = null,
    layout = 'vertical',
    visibleLimit = 5,
    useOptionDefaults = true,
    enableOtherOption = false,
    otherOptionLabel = '',
}) => {
    const normalizedOptions = applyOptionAvailabilityToPreviewOptions(normalizeOptions(options));
    const visibleOptions = normalizedOptions.slice(0, visibleLimit);
    const hiddenCount = Math.max(normalizedOptions.length - visibleOptions.length, 0);
    const selectedValues = normalizeSelectedValues(value, normalizedOptions, useOptionDefaults);
    const inputType = choiceType === 'radio' ? 'radio' : 'checkbox';
    const rowClassName = choiceType === 'radio' ? 'formie-field-preview-radio' : 'formie-field-preview-checkbox';
    const resolvedOtherLabel = String(otherOptionLabel || '').trim() || Craft.t('formie', 'Other');

    return (
        <div className={`formie-field-preview-layout-${layout || 'vertical'}`}>
            {visibleOptions.map((option, index) => {
                const optionValue = String(option?.value ?? '');

                return (
                    <div key={index} className={rowClassName}>
                        <input
                            type={inputType}
                            value={optionValue}
                            checked={selectedValues.includes(optionValue)}
                            disabled={isOptionFrontEndDisabled(option)}
                            readOnly
                        />
                        <label>{option?.label ?? optionValue}</label>
                    </div>
                );
            })}

            {enableOtherOption && choiceType === 'radio' && (
                <div className={cn(rowClassName, 'formie-field-preview-other-option')}>
                    <div className="formie-field-preview-other-option-row">
                        <input
                            type={inputType}
                            value={OTHER_OPTION_VALUE}
                            checked={false}
                            readOnly
                            disabled
                        />
                        <label>{resolvedOtherLabel}</label>
                    </div>
                    <input
                        type="text"
                        className="formie-field-preview-input formie-field-preview-other-option-text"
                        readOnly
                        tabIndex={-1}
                        value=""
                        aria-hidden="true"
                    />
                </div>
            )}

            {hiddenCount > 0 && (
                <div className="formie-field-preview-instructions">
                    ... {hiddenCount} {Craft.t('formie', 'more')}
                </div>
            )}
        </div>
    );
};
