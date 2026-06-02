import React, { useMemo } from 'react';
import { cn } from '@verbb/plugin-kit-react/utils';
import useAppStore from '@form-builder/hooks/useAppStore';

const hasValue = (value) => {
    return value !== null && value !== undefined && value !== '';
};

const SubFieldPreviewControl = ({ field }) => {
    const getFieldTypeByType = useAppStore((state) => { return state.getFieldTypeByType; });
    const value = field?.defaultValue;
    const placeholder = field?.placeholder || '';
    const fieldType = useMemo(() => {
        if (!field?.type || typeof getFieldTypeByType !== 'function') {
            return null;
        }

        return getFieldTypeByType(field.type);
    }, [field?.type, getFieldTypeByType]);
    const fieldTypeIcon = fieldType?.icon || '';
    const hasFieldTypeIcon = Boolean(fieldTypeIcon);
    const showIcon = hasFieldTypeIcon;

    return (
        <div className={cn(
            'formie-field-preview-control',
            showIcon && 'formie-field-preview-control--with-icon',
        )}
        >
            <input
                type="text"
                className="formie-field-preview-input"
                placeholder={placeholder}
                value={hasValue(value) ? value : ''}
                readOnly
            />

            {hasFieldTypeIcon && (
                <span
                    className="formie-field-preview-control-icon formie-field-preview-control-icon--svg"
                    dangerouslySetInnerHTML={{ __html: fieldTypeIcon }}
                />
            )}
        </div>
    );
};

export { SubFieldPreviewControl };
