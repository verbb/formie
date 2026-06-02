import React from 'react';

export const PreviewIcon = ({ icon, className = 'formie-field-preview-icon' }) => {
    if (!icon) {
        return null;
    }

    return (
        <span
            className={className}
            dangerouslySetInnerHTML={{ __html: icon }}
        />
    );
};
