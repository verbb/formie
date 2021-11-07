// import VueFormulate from '@braid/vue-formulate';
import VueFormulate from '@braid/vue-formulate/src/Formulate.js';

import FormFields from '../components/formulate';

export default Vue => {
    Object.values(FormFields).forEach((FormField) => {
        Vue.component(FormField.name, FormField);
    });

    Vue.use(VueFormulate, {
        classes: {
            outer: 'field',
            wrapper: 'field field-wrapper',
            element: (context) => {
                if (context.classification === 'select') {
                    return 'select';
                }

                return 'input';
            },
            help: 'instructions',
            errors: 'errors',
            error: 'error',
        },

        rules: {
            uniqueHandle: (context, args) => {
                return Promise.resolve((() => {
                    var editingField = Vue.prototype.$editingField;

                    if (editingField) {                    
                        return editingField.fieldHandles.indexOf(context.value) === -1;
                    }

                    return true;
                })());
            },

            requiredIf: (context, args) => {
                return Promise.resolve((() => {
                    const values = context.getFormValues();
                    const hasValue = has(values, args);

                    if (!hasValue || !values[args]) {
                        return true;
                    }

                    return !!context.value;
                })());
            },

            requiredIfEqual: (context, args) => {
                return Promise.resolve((() => {
                    const values = context.getFormValues();
                    const [ prop, value ] = args.split('=');

                    if (!values[prop] || values[prop] !== value) {
                        return true;
                    }

                    return !!context.value;
                })());
            },

            requiredIfNotEqual: (context, args) => {
                return Promise.resolve((() => {
                    const values = context.getFormValues();
                    const [ prop, value ] = args.split('=');

                    if (!values[prop] || values[prop] === value) {
                        return true;
                    }

                    return !!context.value;
                })());
            },

            minBlock: (context, args) => {
                return Promise.resolve((() => {
                    // It'd be nice if we could make this a bit more dynamic, with checking props
                    const values = context.getFormValues();

                    if (has(values, 'address1Enabled')) {
                        return values.autocompleteEnabled || values.address1Enabled || values.address2Enabled || values.address3Enabled || values.cityEnabled || values.stateEnabled || values.zipEnabled || values.countryEnabled;
                    }

                    if (has(values, 'prefixEnabled')) {
                        return values.prefixEnabled || values.firstNameEnabled || values.middleNameEnabled || values.lastNameEnabled;
                    }
                })());
            },

            emailOrVariable: (context, args) => {
                return Promise.resolve((() => {
                    const variableRegex = /({.*?})/;
                    const emailRegex = /(^$|^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$)/;

                    if (variableRegex.test(context.value)) {
                        return true;
                    }

                    return emailRegex.test(context.value);
                })());
            },
        },

        locales: {
            en: {
                requiredIf({ name }) {
                    return Craft.t('formie', '{name} is required.', { name });
                },

                uniqueHandle({ name }) {
                    return Craft.t('formie', 'Handle must be unique.');
                },

                minBlock({ name }) {
                    return Craft.t('formie', 'At least one field is required.');
                },
            },
        },

        library: {
            handle: {
                classification: 'text',
                component: 'HandleField',
                slotProps: {
                    label: ['sourceValue', 'collection', 'fieldId'],
                },
            },

            textWithSuffix: {
                classification: 'text',
                component: 'TextWithSuffixField',
            },

            multiSelect: {
                classification: 'select',
                component: 'MultiSelectField',
            },

            lightswitch: {
                component: 'LightswitchField',
            },

            checkbox: {
                classification: 'box',
                component: 'BoxField',
            },

            variableText: {
                classification: 'text',
                component: 'VariableTextField',
            },

            richText: {
                classification: 'text',
                component: 'RichTextField',
            },

            notificationConditions: {
                classification: 'text',
                component: 'NotificationConditions',
            },

            notificationRecipientConditions: {
                classification: 'text',
                component: 'NotificationRecipientConditions',
            },

            fieldConditions: {
                classification: 'text',
                component: 'FieldConditions',
            },

            date: {
                component: 'DateField',
            },

            checkboxSelect: {
                component: 'CheckboxSelectField',
            },

            fieldWrap: {
                component: 'FieldWrap',
            },

            toggleBlocks: {
                component: 'ToggleBlocks',
            },

            toggleBlock: {
                component: 'ToggleBlock',
            },

            collapse: {
                component: 'Collapse',
            },

            table: {
                component: 'TableField',
                slotProps: {
                    repeatable: [
                        'allowMultipleDefault',
                        'newRowLabel',
                        'showHeader',
                        'confirmDelete',
                        'confirmMessage',
                        'newRowDefaults',
                        'enableBulkOptions',
                        'predefinedOptions',
                        'columns',
                        'generateHandle',
                        'generateValue',
                        'useColumnIds',
                    ],
                },
                slotComponents: {
                    repeatable: 'TableRow',
                },
            },

            elementSelect: {
                component: 'ElementSelectField',
            },

            fieldSelect: {
                classification: 'text',
                component: 'FieldSelectField',
            },
        },

        slotComponents: {
            label: 'Label',
            help: 'Help',
        },

        slotProps: {
            label: ['tab', 'required', 'warning'],
        },
    });

};
