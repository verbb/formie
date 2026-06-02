const PREVIEW_TYPE_LIST = [
    'verbb\\formie\\fields\\Address',
    'verbb\\formie\\fields\\Agree',
    'verbb\\formie\\fields\\Date',
    'verbb\\formie\\fields\\Calculations',
    'verbb\\formie\\fields\\Categories',
    'verbb\\formie\\fields\\Checkboxes',
    'verbb\\formie\\fields\\Dropdown',
    'verbb\\formie\\fields\\Email',
    'verbb\\formie\\fields\\Entries',
    'verbb\\formie\\fields\\FileUpload',
    'verbb\\formie\\fields\\Group',
    'verbb\\formie\\fields\\Heading',
    'verbb\\formie\\fields\\Hidden',
    'verbb\\formie\\fields\\Html',
    'verbb\\formie\\fields\\MultiLineText',
    'verbb\\formie\\fields\\Name',
    'verbb\\formie\\fields\\Number',
    'verbb\\formie\\fields\\Payment',
    'verbb\\formie\\fields\\Password',
    'verbb\\formie\\fields\\Phone',
    'verbb\\formie\\fields\\Radio',
    'verbb\\formie\\fields\\Recipients',
    'verbb\\formie\\fields\\Repeater',
    'verbb\\formie\\fields\\Section',
    'verbb\\formie\\fields\\Signature',
    'verbb\\formie\\fields\\SingleLineText',
    'verbb\\formie\\fields\\Summary',
    'verbb\\formie\\fields\\Table',
    'verbb\\formie\\fields\\Tags',
];

const AGREE_DESCRIPTION_DOC = {
    type: 'doc',
    content: [
        {
            type: 'paragraph',
            content: [
                {
                    type: 'text',
                    text: 'I agree to the Terms and Privacy Policy.',
                },
            ],
        },
    ],
};

const EXAMPLE_OPTION_ROWS = [
    {
        label: 'Option One', value: 'one', isDefault: true, disabled: false,
    },
    {
        label: 'Option Two', value: 'two', isDefault: false, disabled: false,
    },
    {
        label: 'Option Three', value: 'three', isDefault: false, disabled: false,
    },
];

const EXAMPLE_OPTION_ROWS_LARGE = [
    {
        label: 'Option One', value: 'one', isDefault: true, disabled: false,
    },
    {
        label: 'Option Two', value: 'two', isDefault: false, disabled: false,
    },
    {
        label: 'Option Three', value: 'three', isDefault: false, disabled: false,
    },
    // {
    //     label: 'Option Three', value: 'three', isDefault: false, disabled: false,
    // },
    // {
    //     label: 'Option Three', value: 'three', isDefault: false, disabled: false,
    // },
    // {
    //     label: 'Option Three', value: 'three', isDefault: false, disabled: false,
    // },
];

const RECIPIENT_OPTION_ROWS = [
    { label: 'Sales', value: 'sales@example.com', isDefault: true },
    { label: 'Support', value: 'support@example.com', isDefault: false },
    { label: 'Accounts', value: 'accounts@example.com', isDefault: false },
];

const PREVIEW_VARIANTS = {
    'verbb\\formie\\fields\\Heading': [
        {
            key: 'h2',
            label: 'Heading (H2)',
            overrides: {
                label: 'Section Heading',
                headingSize: 'h2',
            },
        },
        {
            key: 'h4',
            label: 'Heading (H4)',
            overrides: {
                label: 'Subsection Heading',
                headingSize: 'h4',
            },
        },
    ],
    'verbb\\formie\\fields\\Html': [
        {
            key: 'content',
            label: 'HTML (Content)',
            overrides: {
                htmlContent: '<p><strong>Example HTML content.</strong> You can include links like <a href="https://example.com" target="_blank" rel="noopener noreferrer">this one</a>.</p>',
            },
        },
    ],
    'verbb\\formie\\fields\\Agree': [
        {
            key: 'description',
            label: 'Agree (With Description)',
            overrides: {
                description: AGREE_DESCRIPTION_DOC,
                checkedValue: 'Yes',
                uncheckedValue: 'No',
                defaultValue: false,
            },
        },
    ],
    'verbb\\formie\\fields\\Name': [
        {
            key: 'single',
            label: 'Single Name',
            overrides: {
                useMultipleFields: false,
            },
        },
        {
            key: 'multi-compact',
            label: 'Multi Name (First + Last)',
            overrides: {
                useMultipleFields: true,
                subFieldEnabled: {
                    prefix: false,
                    firstName: true,
                    middleName: false,
                    lastName: true,
                },
            },
        },
        {
            key: 'multi-full',
            label: 'Multi Name (All)',
            overrides: {
                useMultipleFields: true,
                subFieldEnabled: {
                    prefix: true,
                    firstName: true,
                    middleName: true,
                    lastName: true,
                },
            },
        },
    ],
    'verbb\\formie\\fields\\Address': [
        {
            key: 'compact',
            label: 'Address (Compact)',
            overrides: {
                subFieldEnabled: {
                    address1: true,
                    address2: false,
                    address3: false,
                    city: true,
                    zip: true,
                    state: true,
                    country: true,
                    autoComplete: false,
                },
            },
        },
        {
            key: 'full',
            label: 'Address (Full)',
            overrides: {
                subFieldEnabled: {
                    address1: true,
                    address2: true,
                    address3: true,
                    city: true,
                    zip: true,
                    state: true,
                    country: true,
                    autoComplete: true,
                },
            },
        },
    ],
    'verbb\\formie\\fields\\Radio': [
        {
            key: 'vertical',
            label: 'Radio (Vertical)',
            overrides: {
                layout: 'vertical',
                options: EXAMPLE_OPTION_ROWS,
            },
        },
        {
            key: 'horizontal',
            label: 'Radio (Horizontal)',
            overrides: {
                layout: 'horizontal',
                options: EXAMPLE_OPTION_ROWS,
            },
        },
    ],
    'verbb\\formie\\fields\\Checkboxes': [
        {
            key: 'vertical',
            label: 'Checkboxes (Vertical)',
            overrides: {
                layout: 'vertical',
                options: EXAMPLE_OPTION_ROWS,
            },
        },
        {
            key: 'vertical-large',
            label: 'Checkboxes (Vertical Large)',
            overrides: {
                layout: 'vertical',
                options: EXAMPLE_OPTION_ROWS_LARGE,
            },
        },
        {
            key: 'horizontal',
            label: 'Checkboxes (Horizontal)',
            overrides: {
                layout: 'horizontal',
                options: EXAMPLE_OPTION_ROWS,
            },
        },
    ],
    'verbb\\formie\\fields\\Dropdown': [
        {
            key: 'single',
            label: 'Dropdown (Single)',
            overrides: {
                multi: false,
                placeholder: 'Select an option',
                options: EXAMPLE_OPTION_ROWS,
            },
        },
        {
            key: 'multiple',
            label: 'Dropdown (Multiple)',
            overrides: {
                multi: true,
                placeholder: 'Select options',
                options: EXAMPLE_OPTION_ROWS,
            },
        },
    ],
    'verbb\\formie\\fields\\Date': [
        {
            key: 'calendar',
            label: 'Date (Calendar)',
            overrides: {
                displayType: 'calendar',
            },
        },
        {
            key: 'date-picker',
            label: 'Date (Date Picker)',
            overrides: {
                displayType: 'datePicker',
            },
        },
        {
            key: 'dropdowns',
            label: 'Date (Dropdowns)',
            overrides: {
                displayType: 'dropdowns',
            },
        },
        {
            key: 'inputs',
            label: 'Date (Inputs)',
            overrides: {
                displayType: 'inputs',
            },
        },
    ],
    'verbb\\formie\\fields\\Phone': [
        {
            key: 'with-country',
            label: 'Phone (With Country)',
            overrides: {
                countryEnabled: true,
                countryDefaultValue: 'US',
                placeholder: '+1 555 123 4567',
            },
        },
        {
            key: 'without-country',
            label: 'Phone (Without Country)',
            overrides: {
                countryEnabled: false,
                placeholder: '555 123 4567',
            },
        },
    ],
    'verbb\\formie\\fields\\Entries': [
        {
            key: 'dropdown',
            label: 'Entries (Dropdown)',
            overrides: {
                displayType: 'dropdown',
            },
        },
        {
            key: 'checkboxes',
            label: 'Entries (Checkboxes)',
            overrides: {
                displayType: 'checkboxes',
            },
        },
        {
            key: 'radio',
            label: 'Entries (Radio)',
            overrides: {
                displayType: 'radio',
            },
        },
    ],
    'verbb\\formie\\fields\\Categories': [
        {
            key: 'dropdown',
            label: 'Categories (Dropdown)',
            overrides: {
                displayType: 'dropdown',
            },
        },
        {
            key: 'checkboxes',
            label: 'Categories (Checkboxes)',
            overrides: {
                displayType: 'checkboxes',
            },
        },
        {
            key: 'radio',
            label: 'Categories (Radio)',
            overrides: {
                displayType: 'radio',
            },
        },
    ],
    'verbb\\formie\\fields\\Tags': [
        {
            key: 'dropdown',
            label: 'Tags (Dropdown)',
            overrides: {
                displayType: 'dropdown',
            },
        },
        {
            key: 'checkboxes',
            label: 'Tags (Checkboxes)',
            overrides: {
                displayType: 'checkboxes',
            },
        },
        {
            key: 'radio',
            label: 'Tags (Radio)',
            overrides: {
                displayType: 'radio',
            },
        },
    ],
    'verbb\\formie\\fields\\Recipients': [
        {
            key: 'hidden',
            label: 'Recipients (Hidden)',
            overrides: {
                displayType: 'hidden',
            },
        },
        {
            key: 'dropdown',
            label: 'Recipients (Dropdown)',
            overrides: {
                displayType: 'dropdown',
                options: RECIPIENT_OPTION_ROWS,
            },
        },
        {
            key: 'radio',
            label: 'Recipients (Radio)',
            overrides: {
                displayType: 'radio',
                options: RECIPIENT_OPTION_ROWS,
            },
        },
        {
            key: 'checkboxes',
            label: 'Recipients (Checkboxes)',
            overrides: {
                displayType: 'checkboxes',
                options: RECIPIENT_OPTION_ROWS,
            },
        },
    ],
};

const getShortType = (type) => {
    return type.replace('verbb\\formie\\fields\\', '');
};

const sanitizeHandle = (value) => {
    return String(value || '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');
};

const deepClone = (value) => {
    return JSON.parse(JSON.stringify(value || {}));
};

const hasTextLikeValue = (value) => {
    return value === null || value === undefined || typeof value === 'string' || typeof value === 'number';
};

const hasNonEmptyValue = (value) => {
    return value !== null && value !== undefined && value !== '';
};

const normalizeTextValue = (value) => {
    if (value === null || value === undefined) {
        return '';
    }

    return String(value);
};

const applyPreviewValueMode = (field, valueMode = 'normal') => {
    if (!field || valueMode === 'normal') {
        return field;
    }

    const label = field.label || field.handle || 'Field';

    if ('defaultValue' in field && hasTextLikeValue(field.defaultValue)) {
        if (valueMode === 'empty') {
            field.defaultValue = '';
        } else if (valueMode === 'placeholder') {
            field.defaultValue = '';
        } else if (valueMode === 'default' && !hasNonEmptyValue(field.defaultValue)) {
            field.defaultValue = `Example ${label}`;
        }
    }

    if ('placeholder' in field && hasTextLikeValue(field.placeholder)) {
        if (valueMode === 'empty') {
            field.placeholder = '';
        } else if (valueMode === 'placeholder') {
            field.placeholder = hasNonEmptyValue(field.placeholder) ? normalizeTextValue(field.placeholder) : `${label} placeholder`;
        }
    }

    if (Array.isArray(field.rows)) {
        field.rows = field.rows.map((row) => {
            if (!Array.isArray(row?.fields)) {
                return row;
            }

            return {
                ...row,
                fields: row.fields.map((subField) => {
                    return applyPreviewValueMode({ ...subField }, valueMode);
                }),
            };
        });
    }

    return field;
};

const createFieldTypeMap = (settings) => {
    const fieldTypeGroups = settings?.fieldTypeGroups || [];
    const fieldTypes = fieldTypeGroups.flatMap((group) => { return group.fields || []; });
    const map = new Map();

    fieldTypes.forEach((fieldType) => {
        if (fieldType?.type) {
            map.set(fieldType.type, fieldType);
        }
    });

    return map;
};

const applySubFieldEnabledOverrides = (field, enabledMap = {}) => {
    if (!field || !Array.isArray(field.rows) || !enabledMap || typeof enabledMap !== 'object') {
        return field;
    }

    field.rows = field.rows.map((row) => {
        if (!Array.isArray(row?.fields)) {
            return row;
        }

        return {
            ...row,
            fields: row.fields.map((subField) => {
                const handle = subField?.handle;
                if (!handle || !(handle in enabledMap)) {
                    return subField;
                }

                return {
                    ...subField,
                    enabled: Boolean(enabledMap[handle]),
                };
            }),
        };
    });

    return field;
};

const createPreviewField = (type, label, handle, overrides = {}, baseField = null, options = {}) => {
    const initialField = baseField ? deepClone(baseField) : {};
    const { subFieldEnabled, ...restOverrides } = overrides || {};

    const field = {
        ...initialField,
        type,
        label,
        handle,
        ...restOverrides,
    };

    const withSubFieldOverrides = applySubFieldEnabledOverrides(field, subFieldEnabled);

    return applyPreviewValueMode(withSubFieldOverrides, options.valueMode);
};

const createFieldPreviewPages = (settings, options = {}) => {
    const fieldTypeMap = createFieldTypeMap(settings);
    const rows = [];

    PREVIEW_TYPE_LIST.forEach((type) => {
        const shortType = getShortType(type);
        const fieldTypeConfig = fieldTypeMap.get(type);
        const baseField = fieldTypeConfig?.newField || null;
        const variants = PREVIEW_VARIANTS[type] || [];

        if (!variants.length) {
            rows.push({
                fields: [
                    createPreviewField(type, shortType, sanitizeHandle(shortType), {}, baseField, options),
                ],
            });

            return;
        }

        variants.forEach((variant) => {
            const variantLabel = variant.label || `${shortType} (${variant.key})`;
            const variantHandle = sanitizeHandle(`${shortType}_${variant.key}`);
            rows.push({
                fields: [
                    createPreviewField(type, variantLabel, variantHandle, variant.overrides || {}, baseField, options),
                ],
            });
        });
    });

    return [{
        label: 'Field Preview Matrix',
        // handle: 'fieldPreviewMatrix',
        _handle: 'fieldPreviewMatrix',
        rows,
    }];
};

export {
    createFieldPreviewPages,
};
