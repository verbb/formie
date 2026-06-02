import React from 'react';
import { cn } from '@verbb/plugin-kit-react/utils';
import { PreviewIcon } from './PreviewIcon';

export const PreviewInput = ({
    type = 'text',
    wrapperClassName = 'formie-field-preview-control',
    className = 'formie-field-preview-input',
    placeholder = '',
    value = '',
    icon = '',
    readOnly = true,
}) => {
    const hasIcon = Boolean(icon);
    const previewType = type === 'hidden' ? 'text' : type;

    return (
        <div className={cn(wrapperClassName, hasIcon && 'formie-field-preview-control--with-icon')}>
            <input
                type={previewType}
                className={className}
                placeholder={previewType === 'file' ? undefined : placeholder}
                value={previewType === 'file' ? undefined : (value ?? '')}
                readOnly={previewType === 'file' ? undefined : readOnly}
            />

            <PreviewIcon icon={icon} />
        </div>
    );
};
