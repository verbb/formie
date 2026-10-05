import React from 'react';
import { PreviewIcon } from './PreviewIcon';
import { usePreviewSchemaContext } from './PreviewSchemaContext';

export const normalizePhonePreviewValue = (value) => {
    if (value === null || value === undefined) {
        return '';
    }

    if (typeof value === 'string') {
        const trimmedValue = value.trim();

        if (trimmedValue.startsWith('{')) {
            try {
                return normalizePhonePreviewValue(JSON.parse(trimmedValue));
            } catch {
                return value;
            }
        }

        return value;
    }

    if (typeof value === 'object') {
        return normalizePhonePreviewValue(value.number);
    }

    return String(value);
};

export const PreviewPhone = () => {
    const { field, fieldType } = usePreviewSchemaContext();
    const defaultValue = normalizePhonePreviewValue(field?.defaultValue);

    if (field?.countryEnabled) {
        return (
            <div className="formie-field-preview-layout-phone">
                <div className="formie-field-preview-control formie-field-preview-control--phone-combined">
                    <div className="formie-field-preview-phone-country-trigger">
                        <span className="formie-field-preview-phone-country-flag" />
                        <span className="formie-field-preview-phone-country-code">{field.countryDefaultValue || 'US'}</span>
                    </div>

                    <input
                        type="text"
                        className="formie-field-preview-input formie-field-preview-input--phone-inline"
                        placeholder={field.placeholder || Craft.t('formie', 'Phone Number')}
                        value={defaultValue}
                        readOnly
                    />

                    <PreviewIcon icon={fieldType?.icon || ''} />
                </div>
            </div>
        );
    }

    return (
        <div className="formie-field-preview-layout-phone">
            <div className="formie-field-preview-control formie-field-preview-control--phone">
                <input
                    type="text"
                    className="formie-field-preview-input"
                    placeholder={field?.placeholder || Craft.t('formie', 'Phone Number')}
                    value={defaultValue}
                    readOnly
                />

                <PreviewIcon icon={fieldType?.icon || ''} />
            </div>
        </div>
    );
};
