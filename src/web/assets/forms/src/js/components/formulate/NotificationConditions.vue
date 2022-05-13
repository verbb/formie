<template>
    <div>
        <div class="field field-wrapper">
            <div style="margin: 0 0 20px;">
                {{ 'I want to' | t('formie') }}
                <div class="select small">
                    <select v-model="settings.sendRule">
                        <option value="send">{{ 'Send' | t('formie') }}</option>
                        <option value="notSend">{{ 'Not Send' | t('formie') }}</option>
                    </select>
                </div>
                {{ 'this notification if' | t('formie') }}
                <div class="select small">
                    <select v-model="settings.conditionRule">
                        <option value="all">{{ 'All' | t('formie') }}</option>
                        <option value="any">{{ 'Any' | t('formie') }}</option>
                    </select>
                </div>
                {{ 'of the following rules match.' | t('formie') }}
            </div>

            <div class="input">
                <table class="editable fullwidth">
                    <thead>
                        <tr>
                            <th scope="col" class="select-cell thin">{{ 'Field' | t('formie') }}</th>
                            <th scope="col" class="select-cell thin">{{ 'Condition' | t('formie') }}</th>
                            <th scope="col" class="singleline-cell textual">{{ 'Value' | t('formie') }}</th>
                            <th colspan="2"></th>
                        </tr>
                    </thead>
                    <tbody class="">
                        <tr v-for="(row, index) in settings.conditions" :key="row.id">
                            <td class="select-cell thin">
                                <div class="select small">
                                    <select v-model="row.field" @change="changeDropdown(row)">
                                        <option value="">{{ 'Select an option' | t('formie') }}</option>

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
                    {{ 'Add rule' | t('formie') }}
                </div>
            </div>
        </div>

        <div class="hidden">
            <br>

            <textarea v-model="context.model" class="input text fullwidth"></textarea>
        </div>
    </div>
</template>

<script>
import truncate from 'lodash/truncate';

import { newId } from '../../utils/string';
import { toBoolean } from '../../utils/bool';

export default {
    name: 'NotificationConditions',

    props: {
        context: {
            type: Object,
            required: true,
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
                sendRule: 'send',
                conditionRule: 'all',
                conditions: [],
            },
        };
    },

    watch: {
        settings: {
            deep: true,
            handler(newValue) {
                this.context.model = this.serializeContent(newValue);
            },
        },
    },

    created() {
        // Setup custom fields
        this.fieldOptions = this.getFieldOptions();

        // Load up settings into a local variable. Cloned so we don't get collisions
        this.settings = this.unserializeContent(clone(this.context.model));
    },

    methods: {
        unserializeContent(value) {
            var parsedValue = null;

            if (!value) {
                return this.defaultSettings;
            }

            if (!Array.isArray(value)) {
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
                parsedValue.conditions.forEach(row => {
                    var field = this.getField(row.field);
            
                    this.$set(row, 'valueType', this.getValueType(field, row.condition));
                    this.$set(row, 'valueOptions', this.getValueOptions(field, row.condition));
                });

                return parsedValue;
            }

            return this.defaultSettings;
        },

        serializeContent(content) {
            var value = clone(content);

            // Remove value types, they're generated dynamically
            value.conditions.forEach(row => {
                this.$delete(row, 'valueType');
                this.$delete(row, 'valueOptions');
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
                var testField = field;
                var options = field.field.settings.options || [];

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
                var testField = field;
                var options = field.field.settings.options || [];
                
                // Check for group/repeater fields
                if (field.field.supportsNested) {
                    options = field.subfield.settings.options || [];
                    testField = field.subfield;
                }

                // Important to make sure we don't edit the original options, so make un-reactive
                options = clone(options);

                // Special case for agree fields
                if (testField.type === 'verbb\\formie\\fields\\formfields\\Agree') {
                    return [
                        { label: 'Checked', value: '1' },
                        { label: 'Unchecked', value: '0' },
                    ];
                }

                // Special handling for recipients, should use placeholders
                if (testField.type === 'verbb\\formie\\fields\\formfields\\Recipients') {
                    for (var i = 0; i < options.length; i++) {
                        options[i].value = 'id:' + i;
                    }
                }

                // Filter out any optgroups
                options = options.filter(option => {
                    return !option.isOptgroup;
                });
            
                return options;
            }

            // Handle submission options which have statically defined options
            if (field && field.valueOptions) {
                // Important to make sure we don't edit the original options, so make un-reactive
                return clone(field.valueOptions);
            }

            return [];
        },

        changeDropdown(row) {
            var field = this.getField(row.field);

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
            var field = null;

            this.fieldOptions.forEach(optgroup => {
                optgroup.options.forEach(f => {
                    if (f.value === handle) {
                        field = f;
                    }
                });
            });

            return field;
        },

        getFieldOptions() {
            var options = [];

            var allStatuses = this.$store.getters['formie/statuses']();
            var statuses = allStatuses.map(status => {
                return { label: status.name, value: status.handle };
            });

            options.push({
                label: Craft.t('formie', 'Submission'),
                options: [
                    { label: Craft.t('formie', 'Title'), value: '{submission:title}' },
                    { label: Craft.t('formie', 'ID'), value: '{submission:id}' },
                    { label: Craft.t('formie', 'Form Name'), value: '{submission:formName}' },
                    {
                        label: Craft.t('formie', 'Status'),
                        value: '{submission:status}',
                        valueType: 'select',
                        valueOptions: [
                            { label: Craft.t('formie', 'Select an option'), value: '' },
                            ...statuses,
                        ],
                    },
                ],
            });

            var fields = this.$store.getters['form/fields'];
            var customFields = [];

            fields.forEach(field => {
                // If this field is nested itself, don't show. The outer field takes care of that below
                if (!toBoolean(field.isNested)) {
                    if (field.subfieldOptions && field.hasSubfields) {
                        field.subfieldOptions.forEach(subfield => {
                            customFields.push({
                                field,
                                subfield,
                                type: field.type,
                                label: truncate(field.label, { length: 42 }) + ': ' + truncate(subfield.label, { length: 42 }),
                                value: '{' + field.handle + '.' + subfield.handle + '}',
                            });
                        });
                    } else if (field.type === 'verbb\\formie\\fields\\formfields\\Group' && field.rows) {
                        // Is this a group field that supports nesting?
                        field.rows.forEach(row => {
                            row.fields.forEach(subfield => {
                                customFields.push({
                                    field,
                                    subfield,
                                    type: field.type,
                                    label: truncate(field.label, { length: 42 }) + ': ' + truncate(subfield.label, { length: 42 }),
                                    value: '{' + field.handle + '.rows.new1.fields.' + subfield.handle + '}',
                                });
                            });
                        });
                    } else if (field.type === 'verbb\\formie\\fields\\formfields\\Date') {
                        // Special handling for date fields for now
                        customFields.push({ 
                            field,
                            type: field.type,
                            label: truncate(field.label, { length: 42 }), 
                            value: '{' + field.handle + '.date}',
                        });                        
                    } else {
                        customFields.push({ 
                            field,
                            type: field.type,
                            label: truncate(field.label, { length: 42 }), 
                            value: '{' + field.handle + '}',
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
