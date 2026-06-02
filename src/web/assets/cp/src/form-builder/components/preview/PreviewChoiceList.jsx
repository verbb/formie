import React from 'react';
import { normalizeOptions, normalizeSelectedValues } from './previewValueUtils';

export const PreviewChoiceList = ({
    choiceType = 'checkbox',
    options = [],
    value = null,
    layout = 'vertical',
    visibleLimit = 5,
    useOptionDefaults = true,
}) => {
    const normalizedOptions = normalizeOptions(options);
    const visibleOptions = normalizedOptions.slice(0, visibleLimit);
    const hiddenCount = Math.max(normalizedOptions.length - visibleOptions.length, 0);
    const selectedValues = normalizeSelectedValues(value, normalizedOptions, useOptionDefaults);
    const inputType = choiceType === 'radio' ? 'radio' : 'checkbox';
    const rowClassName = choiceType === 'radio' ? 'formie-field-preview-radio' : 'formie-field-preview-checkbox';

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
                            readOnly
                        />
                        <label>{option?.label ?? optionValue}</label>
                    </div>
                );
            })}

            {hiddenCount > 0 && (
                <div className="formie-field-preview-instructions">
                    ... {hiddenCount} {Craft.t('formie', 'more')}
                </div>
            )}
        </div>
    );
};
