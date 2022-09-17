<template>
    <div style="margin-bottom: 20px;">
        {{ t('formie', 'I want to') }}
        <div class="select small">
            <select v-model="settings.showRule">
                <option value="show">{{ t('formie', 'Show') }}</option>
                <option value="hide">{{ t('formie', 'Hide') }}</option>
            </select>
        </div>
        {{ t('formie', descriptionText) }}
        <div class="select small">
            <select v-model="settings.conditionRule">
                <option value="all">{{ t('formie', 'All') }}</option>
                <option value="any">{{ t('formie', 'Any') }}</option>
            </select>
        </div>
        {{ t('formie', 'of the following rules match.') }}
    </div>

    <table class="editable fullwidth">
        <thead>
            <tr>
                <th scope="col" class="select-cell thin">{{ t('formie', 'Field') }}</th>
                <th scope="col" class="select-cell thin">{{ t('formie', 'Condition') }}</th>
                <th scope="col" class="singleline-cell textual">{{ t('formie', 'Value') }}</th>
                <th colspan="2"></th>
            </tr>
        </thead>
        <tbody class="">
            <tr v-for="(row, index) in settings.conditions" :key="row.id">
                <td class="select-cell thin">
                    <div class="select small">
                        <select v-model="row.field" @change="changeDropdown(row)">
                            <option value="">{{ t('formie', 'Select an option') }}</option>

                            <optgroup v-for="(optgroup, i) in fieldOptions" :key="i" :label="optgroup.label">
                                <option v-for="(option, j) in optgroup.options" :key="j" :value="option.value">
                                    {{ option.label }}
                                </option>
                            </optgroup>
                        </select>
                    </div>
                </td>

                <td class="select-cell thin">
                    <div class="select small">
                        <select v-model="row.condition" @change="changeDropdown(row)">
                            <option v-for="(condition, i) in conditions" :key="i" :value="condition.value">
                                {{ condition.label }}
                            </option>
                        </select>
                    </div>
                </td>

                <td v-if="row.valueType === 'text'" class="singleline-cell textual">
                    <textarea v-model="row.value" rows="1" style="min-height: 36px;"></textarea>
                </td>

                <td v-if="row.valueType === 'select'" class="select-cell" style="text-align: left;">
                    <div class="select small">
                        <select v-model="row.value">
                            <option v-for="(option, i) in row.valueOptions" :key="i" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </div>
                </td>

                <td class="thin action">
                    <a class="delete icon" title="Delete" @click.prevent="removeRow(index)"></a>
                </td>
            </tr>
        </tbody>
    </table>

    <div class="btn add icon" @click.prevent="addRow">
        {{ t('formie', 'Add rule') }}
    </div>

    <div class="hidden">
        <br>

        <textarea v-model="context._value" class="input text fullwidth"></textarea>
    </div>
</template>

<script>
import { mapState } from 'vuex';
import { truncate, isPlainObject } from 'lodash-es';

import { newId } from '@utils/string';
import { toBoolean } from '@utils/bool';

export default {
    props: {
        context: {
            type: Object,
            default: () => {},
        },
    },

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
            ],
            settings: {},
            defaultSettings: {
                showRule: 'show',
                conditionRule: 'all',
                conditions: [],
            },
        };
    },

    computed: {
        ...mapState({
            editingField: (state) => { return state.formie.editingField; },
            pages: (state) => { return state.form.pages; },
        }),

        field() {
            if (this.editingField) {
                return this.editingField.field;
            }

            return [];
        },

        descriptionText() {
            return this.context.attrs.descriptionText || 'this field if';
        },
    },

    watch: {
        settings: {
            deep: true,
            handler(newValue) {
                this.context.node.input(this.serializeContent(newValue));
            },
        },
    },

    created() {
        // Setup custom fields
        this.fieldOptions = this.getFieldOptions();

        // Load up settings into a local variable. Cloned so we don't get collisions
        this.settings = this.unserializeContent(this.clone(this.context._value));
    },

    methods: {
        unserializeContent(value) {
            let parsedValue = null;

            if (!value) {
                return this.defaultSettings;
            }

            if (!Array.isArray(value) && !isPlainObject(value)) {
                try {
                    parsedValue = JSON.parse(value);
                } catch (e) {
                    console.log(e);
                    console.log(value);
                }
            } else {
                parsedValue = value;
            }

            if (parsedValue && parsedValue.conditions) {
                // Prep rows with their correct value types
                parsedValue.conditions.forEach((row) => {
                    const field = this.getField(row.field);

                    row.valueType = this.getValueType(field, row.condition);
                    row.valueOptions = this.getValueOptions(field, row.condition);
                });

                return parsedValue;
            }

            return this.defaultSettings;
        },

        serializeContent(content) {
            const value = this.clone(content);

            // Remove value types, they're generated dynamically
            value.conditions.forEach((row) => {
                delete row.valueType;
                delete row.valueOptions;
            });

            return JSON.stringify(value);
        },

        addRow() {
            this.settings.conditions.push({
                id: newId(),
                field: '',
                condition: '',
                value: '',
                valueType: 'text',
                valueOptions: [],
            });
        },

        removeRow(index) {
            this.settings.conditions.splice(index, 1);
        },

        getValueType(field, condition) {
            // Check if there are any specific options
            if (field && field.field && field.field.settings) {
                let testField = field;
                let options = field.field.settings.options || [];

                // Check for group/repeater fields
                if (field.field.supportsNested) {
                    options = field.subfield.settings.options || [];
                    testField = field.subfield;
                }

                // Only allow picking for 'is' and 'is not'
                if (options.length && ['=', '!='].includes(condition)) {
                    return 'select';
                }

                // Special case for agree fields
                if (testField.type === 'verbb\\formie\\fields\\formfields\\Agree' && ['=', '!='].includes(condition)) {
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
            if (field && field.field && field.field.settings) {
                let testField = field;
                let options = field.field.settings.options || [];

                // Check for group/repeater fields
                if (field.field.supportsNested) {
                    options = field.subfield.settings.options || [];
                    testField = field.subfield;
                }

                // Important to make sure we don't edit the original options, so make un-reactive
                options = this.clone(options);

                // Special case for agree fields
                if (testField.type === 'verbb\\formie\\fields\\formfields\\Agree') {
                    return [
                        { label: 'Checked', value: '1' },
                        { label: 'Unchecked', value: '0' },
                    ];
                }

                // Special handling for recipients, should use placeholders
                if (testField.type === 'verbb\\formie\\fields\\formfields\\Recipients') {
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

        getFieldOptions() {
            const options = [];
            const customFields = [];
            const excludedFields = [];

            const fields = this.$store.getters['form/fields'];
            const allStatuses = this.$store.getters['formie/statuses']();

            const statuses = allStatuses.map((status) => {
                return { label: status.name, value: status.handle };
            });

            // Special-case for page conditions, we don't want to include any fields that are on a future page
            if (this.context.attrs.isPageModal) {
                // First, collect the handles of all field on this page and previous ones.
                const currentPageIndex = this.pages.indexOf(this.context.attrs.page);

                if (currentPageIndex > -1) {
                    this.pages.forEach((page, index) => {
                        if (index > currentPageIndex) {
                            if (page.rows && Array.isArray(page.rows)) {
                                page.rows.forEach((row) => {
                                    if (row.fields && Array.isArray(row.fields)) {
                                        row.fields.forEach((field) => {
                                            excludedFields.push(field.handle);
                                        });
                                    }
                                });
                            }
                        }
                    });
                }
            }

            fields.forEach((field) => {
                // Don't allow conditions on _this_ field
                if (this.field.vid === field.vid) {
                    return;
                }

                // Is this an excluded field?
                if (excludedFields.includes(field.handle)) {
                    return;
                }

                // If this field is nested itself, don't show. The outer field takes care of that below
                if (!toBoolean(field.isNested)) {
                    if (field.subfieldOptions && field.hasSubfields) {
                        field.subfieldOptions.forEach((subfield) => {
                            customFields.push({
                                field,
                                subfield,
                                type: field.type,
                                label: `${truncate(field.label, { length: 60 })}: ${truncate(subfield.label, { length: 60 })}`,
                                value: `{${field.handle}.${subfield.handle}}`,
                            });
                        });
                    } else if (field.type === 'verbb\\formie\\fields\\formfields\\Group' && field.rows) {
                        // Is this a group field that supports nesting?
                        field.rows.forEach((row) => {
                            row.fields.forEach((subfield) => {
                                customFields.push({
                                    field,
                                    subfield,
                                    type: field.type,
                                    label: `${truncate(field.label, { length: 60 })}: ${truncate(subfield.label, { length: 60 })}`,
                                    value: `{${field.handle}.rows.new1.fields.${subfield.handle}}`,
                                });
                            });
                        });
                    } else if (field.type === 'verbb\\formie\\fields\\formfields\\Date') {
                        // Special handling for date fields for now
                        customFields.push({
                            field,
                            type: field.type,
                            label: truncate(field.label, { length: 60 }),
                            value: `{${field.handle}.date}`,
                        });
                    } else {
                        customFields.push({
                            field,
                            type: field.type,
                            label: truncate(field.label, { length: 60 }),
                            value: `{${field.handle}}`,
                        });
                    }
                }
            });

            options.push({
                label: Craft.t('formie', 'Fields'),
                options: customFields,
            });

            return options;
        },
    },
};

</script>
