import React from 'react';

export const PreviewGroup = ({ label }) => {
    return (
        <div className="formie-field-preview-container">
            <div className="formie-field-preview-group">
                <div className="formie-field-preview-group-content">
                    {label || Craft.t('formie', 'Field Group')}
                </div>
            </div>
        </div>
    );
};
