import { truncate, isPlainObject } from 'lodash-es';

import { toBoolean } from '@utils/bool';

export default {
    data() {
        return {
            fieldOptions: [],
            conditions: [
                { label: Craft.t('formie', 'Select an option'), value: '' },
                { label: Craft.t('formie', 'is'), value: '=' },
                { label: Craft.t('formie', 'is not'), value: '!=' },
                { label: Craft.t('formie', 'greater than'), value: '>' },
                { label: Craft.t('formie', 'less than'), value: '<' },
                { label: Craft.t('formie', 'contains'), value: 'contains' },
                { label: Craft.t('formie', 'starts with'), value: 'startsWith' },
                { label: Craft.t('formie', 'ends with'), value: 'endsWith' },
                { label: Craft.t('formie', 'is empty'), value: 'empty' },
                { label: Craft.t('formie', 'is not empty'), value: 'notEmpty' },
                { label: Craft.t('formie', 'is visible'), value: 'visible' },
                { label: Craft.t('formie', 'is hidden'), value: 'hidden' },
            ],
            settings: {},
            defaultSettings: {
                showRule: 'show',
                conditionRule: 'all',
                conditions: [],
            },
        };
    },

    methods: {
        getValueType(field, condition) {
            // Check if there are any specific options
            if (field && field.settings) {
                let testField = field;
                let options = field.settings.options || [];

                // Check for group/repeater fields
                if (field.hasNestedFields) {
                    options = field.subField.settings.options || [];
                    testField = field.subField;
                }

                // Only allow picking for 'is' and 'is not'
                if (options.length && ['=', '!='].includes(condition)) {
                    return 'select';
                }

                // Special case for agree fields
                if (testField.type === 'verbb\\formie\\fields\\Agree' && ['=', '!='].includes(condition)) {
                    return 'select';
                }
            }

            // Handle submission options which have statically defined options
            if (field && field.valueType) {
                // Only allow picking for 'is' and 'is not'
                if (['=', '!='].includes(condition)) {
                    return field.valueType;
                }
            }

            return 'text';
        },

        getValueOptions(field, condition) {
            // Check if there are any specific options
            if (field && field.settings) {
                let testField = field;
                let options = field.settings.options || [];

                // Check for group/repeater fields
                if (field.hasNestedFields) {
                    options = field.subField.settings.options || [];
                    testField = field.subField;
                }

                // Important to make sure we don't edit the original options, so make un-reactive
                options = this.clone(options);

                // Special case for agree fields
                if (testField.type === 'verbb\\formie\\fields\\Agree') {
                    return [
                        { label: 'Checked', value: '1' },
                        { label: 'Unchecked', value: '0' },
                    ];
                }

                // Special handling for recipients, should use placeholders
                if (testField.type === 'verbb\\formie\\fields\\Recipients') {
                    for (let i = 0; i < options.length; i++) {
                        options[i].value = `id:${i}`;
                    }
                }

                // Filter out any optgroups
                options = options.filter((option) => {
                    return !option.isOptgroup;
                });

                return options;
            }

            // Handle submission options which have statically defined options
            if (field && field.valueOptions) {
                // Important to make sure we don't edit the original options, so make un-reactive
                return this.clone(field.valueOptions);
            }

            return [];
        },

        changeDropdown(row) {
            const field = this.getField(row.field);

            row.valueType = this.getValueType(field, row.condition);
            row.valueOptions = this.getValueOptions(field, row.condition);

            // Update row value to pick first option, if there is an option
            if (row.valueType === 'select' && row.valueOptions && row.valueOptions[0]) {
                // eslint-disable-next-line
                row.value = row.valueOptions[0].value;
            } else {
                row.value = '';
            }
        },

        getField(handle) {
            let field = null;

            this.fieldOptions.forEach((optgroup) => {
                optgroup.options.forEach((f) => {
                    if (f.value === handle) {
                        field = f;
                    }
                });
            });

            return field;
        },

        customFieldOptions() {
            return this.$store.getters['form/getConditionsFieldOptions']();
        },
    },
};
