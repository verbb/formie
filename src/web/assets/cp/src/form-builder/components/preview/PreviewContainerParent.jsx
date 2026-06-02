import React from 'react';
import { cn } from '@verbb/plugin-kit-react/utils';

import { ChildFieldPreview } from './ChildFieldPreview';
import { SubFieldPreviewControl } from './SubFieldPreviewControl';
import { usePreviewSchemaContext } from './PreviewSchemaContext';

const isPreviewSubFieldRequired = (subField) => {
    if (typeof subField?.required === 'boolean') {
        return subField.required;
    }

    if (typeof subField?.settings?.required === 'boolean') {
        return subField.settings.required;
    }

    return false;
};

export const PreviewContainerParent = ({
    rows = [],
    showFallbackControl = true,
}) => {
    const { field } = usePreviewSchemaContext();
    const normalizedRows = Array.isArray(rows) ? rows : [];

    return (
        <div className="formie-field-preview-layout-parent">
            {normalizedRows.map((row, rowIndex) => {
                const visibleFields = (row?.fields || []).filter((subField) => {
                    return subField?.enabled !== false;
                });

                if (!visibleFields.length) {
                    return null;
                }

                return (
                    <div key={rowIndex} className="formie-field-preview-layout-child-row">
                        {visibleFields.map((subField, fieldIndex) => {
                            return (
                                <div key={fieldIndex} className="formie-field-preview-layout-child">
                                    <div
                                        className={cn(
                                            'formie-field-preview-sub-label',
                                            'inline-flex items-center gap-1 max-w-full min-w-0',
                                        )}
                                    >
                                        <span className="truncate">{subField.label}</span>
                                        {isPreviewSubFieldRequired(subField) && (
                                            <span className="text-error shrink-0">*</span>
                                        )}
                                    </div>
                                    <ChildFieldPreview field={subField} />
                                </div>
                            );
                        })}
                    </div>
                );
            })}

            {showFallbackControl && normalizedRows.length === 0 && (
                <SubFieldPreviewControl field={field} />
            )}
        </div>
    );
};
