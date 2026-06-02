import React from 'react';
import { usePreviewSchemaContext } from './PreviewSchemaContext';

export const PreviewPayment = ({ value = null }) => {
    const { field } = usePreviewSchemaContext();
    const inputValue = value ?? field?.defaultValue ?? '';

    return (
        <div className="formie-field-preview-layout-payment">
            <div className="formie-field-preview-control">
                <input
                    type="text"
                    className="formie-field-preview-input"
                    placeholder={Craft.t('formie', 'Card number')}
                    value={inputValue}
                    readOnly
                />
            </div>

            <div className="formie-field-preview-control formie-field-preview-control--payment-meta">
                <input
                    type="text"
                    className="formie-field-preview-input"
                    placeholder={Craft.t('formie', 'MM / YY CVC')}
                    value=""
                    readOnly
                />
            </div>
        </div>
    );
};
