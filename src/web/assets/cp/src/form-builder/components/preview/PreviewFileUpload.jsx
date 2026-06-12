import React from 'react';
import { PreviewInput } from './PreviewInput';
import { PreviewUploadManager } from './PreviewUploadManager';

export const PreviewFileUpload = ({
    displayType = 'fileInput',
    icon = '',
}) => {
    if (displayType === 'uploadManager') {
        return <PreviewUploadManager icon={icon} />;
    }

    return (
        <PreviewInput
            type="file"
            icon={icon}
            wrapperClassName="formie-field-preview-control formie-field-preview-control--file"
            className="formie-field-preview-input formie-field-preview-file"
        />
    );
};
