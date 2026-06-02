import React, { useMemo } from 'react';

const getFieldValue = (field, key, fallback = undefined) => {
    if (!field || typeof field !== 'object') {
        return fallback;
    }

    if (field[key] !== undefined) {
        return field[key];
    }

    if (field.settings && field.settings[key] !== undefined) {
        return field.settings[key];
    }

    return fallback;
};

const getDefaultValueOption = (field) => {
    const options = getFieldValue(field, 'defaultValueOptions', []);

    if (Array.isArray(options) && options.length && options[0]) {
        return options[0];
    }

    return {};
};

const toSelectedValues = (defaultValue) => {
    if (Array.isArray(defaultValue)) {
        return defaultValue.map((value) => {
            if (value && typeof value === 'object' && 'value' in value) {
                return String(value.value);
            }

            return String(value);
        });
    }

    if (defaultValue && typeof defaultValue === 'object' && 'value' in defaultValue) {
        return [String(defaultValue.value)];
    }

    if (defaultValue === null || defaultValue === undefined || defaultValue === '') {
        return [];
    }

    return [String(defaultValue)];
};

const FALLBACK_PREVIEW_OPTIONS = [
    { label: 'Example One', value: 'one' },
    { label: 'Example Two', value: 'two' },
    { label: 'Example Three', value: 'three' },
];
const MAX_VISIBLE_OPTIONS = 5;

const normalizeOptions = (fieldOptions) => {
    if (!Array.isArray(fieldOptions) || !fieldOptions.length) {
        return FALLBACK_PREVIEW_OPTIONS;
    }

    return fieldOptions.map((option, index) => {
        const value = option?.value ?? `option-${index + 1}`;
        const label = option?.label ?? String(value);

        return {
            label,
            value: String(value),
        };
    });
};

const FormieElementFieldPreview = ({ field }) => {
    const displayType = getFieldValue(field, 'displayType', 'dropdown');
    const layout = getFieldValue(field, 'layout', 'vertical');
    const layoutClassName = `formie-field-preview-layout-${layout || 'vertical'}`;
    const isMulti = Boolean(getFieldValue(field, 'multi', false));
    const placeholder = getFieldValue(field, 'placeholder', '');
    const defaultValue = getFieldValue(field, 'defaultValue', null);
    const defaultValueOption = getDefaultValueOption(field);
    const selectedValues = useMemo(() => {
        return toSelectedValues(defaultValue);
    }, [defaultValue]);
    const options = useMemo(() => {
        const fieldOptions = getFieldValue(field, 'options', null);
        return normalizeOptions(fieldOptions);
    }, [field]);
    const visibleOptions = options.slice(0, MAX_VISIBLE_OPTIONS);
    const hiddenCount = Math.max(options.length - visibleOptions.length, 0);

    if (displayType === 'dropdown') {
        return (
            <select
                className="formie-field-preview-select"
                multiple={isMulti}
                value={isMulti ? selectedValues : (selectedValues[0] || '')}
                onChange={() => {}}
                disabled
            >
                <option value="">{defaultValueOption.label || placeholder || ''}</option>

                {options.map((option, index) => {
                    return (
                        <option key={index} value={String(option.value)}>
                            {option.label}
                        </option>
                    );
                })}
            </select>
        );
    }

    if (displayType === 'checkboxes') {
        return (
            <div className={`formie-field-preview-checkboxes ${layoutClassName}`}>
                {visibleOptions.map((option, index) => {
                    return (
                        <div key={index} className="formie-field-preview-checkbox">
                            <input
                                type="checkbox"
                                value={option.value}
                                checked={selectedValues.includes(String(option.value))}
                                readOnly
                            />
                            <label>{option.label}</label>
                        </div>
                    );
                })}

                {hiddenCount > 0 && (
                    <div className="formie-field-preview-instructions">
                        ... {hiddenCount} {Craft.t('formie', 'more')}
                    </div>
                )}
            </div>
        );
    }

    if (displayType === 'radio') {
        return (
            <div className={`formie-field-preview-radios ${layoutClassName}`}>
                {visibleOptions.map((option, index) => {
                    return (
                        <div key={index} className="formie-field-preview-radio">
                            <input
                                type="radio"
                                value={option.value}
                                checked={selectedValues.includes(String(option.value))}
                                readOnly
                            />
                            <label>{option.label}</label>
                        </div>
                    );
                })}

                {hiddenCount > 0 && (
                    <div className="formie-field-preview-instructions">
                        ... {hiddenCount} {Craft.t('formie', 'more')}
                    </div>
                )}
            </div>
        );
    }

    return null;
};

export { FormieElementFieldPreview };
