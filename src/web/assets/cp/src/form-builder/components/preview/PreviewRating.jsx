import React from 'react';
import { normalizeOptions, normalizeSelectedValues } from './previewValueUtils';
import {
    applyOptionAvailabilityToPreviewOptions,
    isOptionFrontEndDisabled,
} from '@form-builder/utils/optionAvailability';
import { ratingStarPresentationStyle } from './previewSurveyStarUrls';

export const PreviewRating = ({
    options = [],
    value = null,
    useOptionDefaults = true,
    starColor = null,
}) => {
    const normalizedOptions = applyOptionAvailabilityToPreviewOptions(normalizeOptions(options));
    const selectedValues = normalizeSelectedValues(value, normalizedOptions, useOptionDefaults);
    const selectedIndex = normalizedOptions.findIndex((option) => {
        return selectedValues.includes(String(option?.value ?? ''));
    });

    if (normalizedOptions.length === 0) {
        return (
            <div className="formie-field-preview-rating formie-field-preview-rating--empty">
                <div className="formie-field-preview-instructions">
                    {Craft.t('formie', 'Add choices to build your rating scale.')}
                </div>
            </div>
        );
    }

    const starStyle = ratingStarPresentationStyle(starColor);

    return (
        <div
            className="formie-field-preview-rating"
            data-formie-preview-rating-value={selectedIndex >= 0 ? selectedIndex + 1 : 0}
        >
            <div className="formie-field-preview-rating-stars" role="presentation" style={starStyle}>
                {normalizedOptions.map((option, index) => {
                    const optionValue = String(option?.value ?? '');
                    const optionLabel = option?.label ?? optionValue;

                    return (
                        <label
                            key={index}
                            className="formie-field-preview-rating-option"
                            title={optionLabel}
                        >
                            <input
                                type="radio"
                                value={optionValue}
                                checked={selectedValues.includes(optionValue)}
                                disabled={isOptionFrontEndDisabled(option)}
                                readOnly
                            />
                            <span className="formie-field-preview-rating-star" aria-hidden="true" />
                            <span className="formie-sr-only">{optionLabel}</span>
                        </label>
                    );
                })}
            </div>
        </div>
    );
};
