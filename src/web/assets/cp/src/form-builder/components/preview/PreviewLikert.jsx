import React, { useId, useMemo } from 'react';
import { normalizeOptions, normalizeSelectedValues } from './previewValueUtils';
import {
    applyOptionAvailabilityToPreviewOptions,
    isOptionFrontEndDisabled,
} from '@form-builder/utils/optionAvailability';

function getEffectiveLikertRows(likertRows, multipleRowsEnabled) {
    if (!multipleRowsEnabled || !Array.isArray(likertRows)) {
        return [];
    }

    const rows = [];

    likertRows.forEach((row, index) => {
        const label = String(row?.label ?? '').trim();

        if (label === '') {
            return;
        }

        const value = String(row?.value ?? '').trim() || `preview-row-${index}`;

        rows.push({
            label,
            value,
        });
    });

    return rows;
}

function normalizeMultipleRowsValue(value) {
    if (!value || typeof value !== 'object' || Array.isArray(value)) {
        return {};
    }

    return value;
}

export const PreviewLikert = ({
    options = [],
    likertRows = [],
    multipleRowsEnabled = false,
    value = null,
    useOptionDefaults = true,
}) => {
    const previewId = useId();
    const normalizedOptions = applyOptionAvailabilityToPreviewOptions(normalizeOptions(options));
    const effectiveRows = useMemo(
        () => getEffectiveLikertRows(likertRows, multipleRowsEnabled),
        [likertRows, multipleRowsEnabled],
    );
    const usesMultipleRows = effectiveRows.length >= 2;
    const selectedValues = normalizeSelectedValues(value, normalizedOptions, useOptionDefaults && !usesMultipleRows);
    const multipleRowSelections = normalizeMultipleRowsValue(value);

    if (normalizedOptions.length === 0) {
        return (
            <div className="formie-field-preview-likert formie-field-preview-likert--empty">
                <div className="formie-field-preview-instructions">
                    {Craft.t('formie', 'Add choices to build your Likert scale.')}
                </div>
            </div>
        );
    }

    const table = (
        <table className={`formie-field-preview-likert${usesMultipleRows ? ' formie-field-preview-likert--multiple-rows' : ''}`}>
            <thead>
                <tr className="formie-field-preview-likert-labels">
                    {usesMultipleRows ? (
                        <th
                            className="formie-field-preview-likert-row-label-header"
                            scope="col"
                            role="presentation"
                        />
                    ) : null}
                    {normalizedOptions.map((option, index) => {
                        const optionLabel = option?.label ?? option?.value ?? '';

                        return (
                            <th
                                key={index}
                                id={`${previewId}-col-${index}`}
                                scope="col"
                                role="presentation"
                                className="formie-field-preview-likert-label"
                            >
                                {optionLabel}
                            </th>
                        );
                    })}
                </tr>
            </thead>
            <tbody>
                {usesMultipleRows ? effectiveRows.map((likertRow, rowIndex) => {
                    const rowKey = String(likertRow?.value ?? rowIndex);
                    const rowLabel = likertRow?.label ?? rowKey;

                    return (
                        <tr
                            key={rowKey}
                            className={`formie-field-preview-likert-inputs${rowIndex % 2 === 1 ? ' formie-field-preview-likert-inputs--alt' : ''}`}
                        >
                            <th
                                scope="row"
                                className="formie-field-preview-likert-row-label"
                            >
                                {rowLabel}
                            </th>
                            {normalizedOptions.map((option, index) => {
                                const optionValue = String(option?.value ?? '');
                                const optionLabel = option?.label ?? optionValue;
                                const selected = String(multipleRowSelections[rowKey] ?? '') === optionValue;

                                return (
                                    <td
                                        key={index}
                                        className="formie-field-preview-likert-option formie-field-preview-radio"
                                        data-label={optionLabel}
                                    >
                                        <input
                                            type="radio"
                                            value={optionValue}
                                            checked={selected}
                                            disabled={isOptionFrontEndDisabled(option)}
                                            readOnly
                                            aria-labelledby={`${previewId}-col-${index}`}
                                        />
                                        <label aria-hidden="true">
                                            <span className="formie-field-preview-likert-option-label-text">
                                                {optionLabel}
                                            </span>
                                        </label>
                                    </td>
                                );
                            })}
                        </tr>
                    );
                }) : (
                    <tr className="formie-field-preview-likert-inputs">
                        {normalizedOptions.map((option, index) => {
                            const optionValue = String(option?.value ?? '');
                            const optionLabel = option?.label ?? optionValue;

                            return (
                                <td
                                    key={index}
                                    className="formie-field-preview-likert-option formie-field-preview-radio"
                                    data-label={optionLabel}
                                >
                                    <input
                                        type="radio"
                                        value={optionValue}
                                        checked={selectedValues.includes(optionValue)}
                                        disabled={isOptionFrontEndDisabled(option)}
                                        readOnly
                                        aria-labelledby={`${previewId}-col-${index}`}
                                    />
                                    <label aria-hidden="true">
                                        <span className="formie-field-preview-likert-option-label-text">
                                            {optionLabel}
                                        </span>
                                    </label>
                                </td>
                            );
                        })}
                    </tr>
                )}
            </tbody>
        </table>
    );

    return (
        <div className="formie-field-preview-likert-scroller">
            {table}
        </div>
    );
};
