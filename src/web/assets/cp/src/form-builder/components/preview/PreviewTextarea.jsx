import React from 'react';
import { cn } from '@verbb/plugin-kit-react/utils';
import { PreviewIcon } from './PreviewIcon';

export const PreviewTextarea = ({
    wrapperClassName = 'formie-field-preview-control formie-field-preview-control--multiline',
    className = 'formie-field-preview-input formie-field-preview-textarea',
    placeholder = '',
    value = '',
    icon = '',
    readOnly = true,
}) => {
    const hasIcon = Boolean(icon);

    return (
        <div className={cn(wrapperClassName, hasIcon && 'formie-field-preview-control--with-icon')}>
            <textarea
                className={className}
                placeholder={placeholder}
                value={value ?? ''}
                readOnly={readOnly}
            />

            <PreviewIcon icon={icon} />
        </div>
    );
};
