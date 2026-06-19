import React from 'react';
import { normalizeOptions } from './previewValueUtils';
import {
    applyOptionAvailabilityToPreviewOptions,
} from '@form-builder/utils/optionAvailability';

const RankHandleIcon = () => (
    <svg className="formie-field-preview-rank-handle-icon" viewBox="0 0 640 640" aria-hidden="true">
        <path
            fill="currentColor"
            d="M288 128C288 92.7 259.3 64 224 64C188.7 64 160 92.7 160 128C160 163.3 188.7 192 224 192C259.3 192 288 163.3 288 128zM288 320C288 284.7 259.3 256 224 256C188.7 256 160 284.7 160 320C160 355.3 188.7 384 224 384C259.3 384 288 355.3 288 320zM160 512C160 547.3 188.7 576 224 576C259.3 576 288 547.3 288 512C288 476.7 259.3 448 224 448C188.7 448 160 476.7 160 512zM480 128C480 92.7 451.3 64 416 64C380.7 64 352 92.7 352 128C352 163.3 380.7 192 416 192C451.3 192 480 163.3 480 128zM352 320C352 355.3 380.7 384 416 384C451.3 384 480 355.3 480 320C480 284.7 451.3 256 416 256C380.7 256 352 284.7 352 320zM480 512C480 476.7 451.3 448 416 448C380.7 448 352 476.7 352 512C352 547.3 380.7 576 416 576C451.3 576 480 547.3 480 512z"
        />
    </svg>
);

export const PreviewRank = ({
    options = [],
    visibleLimit = 5,
}) => {
    const normalizedOptions = applyOptionAvailabilityToPreviewOptions(normalizeOptions(options));
    const visibleOptions = normalizedOptions.slice(0, visibleLimit);
    const hiddenCount = Math.max(normalizedOptions.length - visibleOptions.length, 0);

    if (visibleOptions.length === 0) {
        return (
            <div className="formie-field-preview-rank formie-field-preview-rank--empty">
                <div className="formie-field-preview-instructions">
                    {Craft.t('formie', 'Add choices to build your ranking list.')}
                </div>
            </div>
        );
    }

    return (
        <ul className="formie-field-preview-rank-list">
            {visibleOptions.map((option, index) => (
                <li key={index} className="formie-field-preview-rank-item">
                    <span className="formie-field-preview-rank-handle" aria-hidden="true">
                        <RankHandleIcon />
                    </span>
                    <span className="formie-field-preview-rank-label">
                        {option?.label ?? option?.value ?? ''}
                    </span>
                </li>
            ))}

            {hiddenCount > 0 && (
                <li className="formie-field-preview-rank-more">
                    <div className="formie-field-preview-instructions">
                        ... {hiddenCount} {Craft.t('formie', 'more')}
                    </div>
                </li>
            )}
        </ul>
    );
};
