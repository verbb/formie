import React from 'react';
import { cn } from '@verbb/plugin-kit-react/utils';
import { PreviewIcon } from './PreviewIcon';

export const PreviewUploadManager = ({
    wrapperClassName = 'formie-field-preview-control formie-field-preview-control--upload-manager',
    className = 'formie-field-preview-upload-manager',
    icon = '',
}) => {
    const hasIcon = Boolean(icon);

    return (
        <div className={cn(wrapperClassName, hasIcon && 'formie-field-preview-control--with-icon')}>
            <div className={className}>
                <span className="formie-field-preview-upload-manager-prompt">
                    {Craft.t('formie', 'Drop files here or browse to upload.')}
                </span>
                <span className="btn formie-field-preview-upload-manager-button">
                    {Craft.t('formie', 'Browse files')}
                </span>
            </div>

            <PreviewIcon icon={icon} />
        </div>
    );
};
