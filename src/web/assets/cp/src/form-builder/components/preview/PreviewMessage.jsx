import React from 'react';

export const PreviewMessage = ({ message = '', className = 'formie-field-preview-input' }) => {
    return (
        <div className={className}>
            {message}
        </div>
    );
};
