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
                                        <option v-for="(field, i) in fields" :key="i" :value="field.value">
                                            {{ field.label }}
                                        </option>
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
            fields: [
                { label: Craft.t('formie', 'Select an option'), value: '' },
            ],
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
        var allFields = this.$store.getters['form/fields'];

        // Load up settings into a local variable. Cloned so we don't get collisions
        this.settings = this.unserializeContent(clone(this.context.model));

        allFields.forEach(field => {
            // If this field is nested itself, don't show. The outer field takes care of that below
            if (!toBoolean(field.isNested)) {
                if (field.subfieldOptions && field.hasSubfields) {
                    field.subfieldOptions.forEach(subfield => {
                        this.fields.push({
                            field,
                            subfield,
                            type: field.type,
                            label: field.label + ': ' + subfield.label,
                            value: '{' + field.handle + '.' + subfield.handle + '}',
                        });
                    });
                } else if (field.type === 'verbb\\formie\\fields\\formfields\\Group' && field.rows) {
                    // Is this a group field that supports nesting?
                    field.rows.forEach(row => {
                        row.fields.forEach(subfield => {
                            this.fields.push({
                                field,
                                subfield,
                                type: field.type,
                                label: field.label + ': ' + subfield.label,
                                value: '{' + field.handle + '.one().' + subfield.handle + ' ?? null}',
                            });
                        });
                    });
                } else {
                    this.fields.push({ 
                        field,
                        type: field.type,
                        label: field.label, 
                        value: '{' + field.handle + '}',
                    });
                }
            }
        });
    },

    methods: {
        unserializeContent(value) {
            var parsedValue = null;

            if (!value) {
                return this.defaultSettings;
            }

            try {
                parsedValue = JSON.parse(value);
            } catch (e) {
                console.log(e);
            }

            if (parsedValue) {
                // Prep rows with their correct value types
                parsedValue.conditions.forEach(row => {
                    this.$set(row, 'valueType', this.getValueType(row.field));
                    this.$set(row, 'valueOptions', this.getValueOptions(row.field));
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
                var options = field.field.settings.options || [];

                // Only allow picking for 'is' and 'is not'
                if (options.length && ['=', '!='].includes(condition)) {
                    return 'select';
                }
            }

            return 'text';
        },

        getValueOptions(field, condition) {
            // Check if there are any specific options
            if (field && field.field && field.field.settings) {
                return field.field.settings.options || [];
            }

            return [];
        },

        changeDropdown(row) {
            var field = this.fields.find(field => {
                return field.value === row.field;
            });

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
    },
};

</script>
