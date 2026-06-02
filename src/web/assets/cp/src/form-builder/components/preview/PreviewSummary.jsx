import React from 'react';

export const PreviewSummary = ({ description = '', message }) => {
    return (
        <div style={{ color: 'var(--tw-color-gray-300)' }}>
            {typeof description === 'string' && description && (
                <h2>{description}</h2>
            )}

            <i>{message}</i>
        </div>
    );
};
