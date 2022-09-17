<template>
    <div class="field">
        <div class="heading">
            <label :id="id + '-label'" :for="id">{{ label }}</label>

            <div class="instructions">
                <p>{{ instructions }}</p>
            </div>
        </div>

        <div class="fui-element-mapping input ltr">
            <div class="select">
                <select v-model="proxyValue" :name="name">
                    <option value="">{{ t('formie', 'Always Opt-in') }}</option>

                    <option v-for="(option, j) in getFieldOptions()" :key="j" :value="option.value">
                        {{ option.label }}
                    </option>
                </select>
            </div>
        </div>
    </div>
</template>

<script>
import { truncate } from 'lodash-es';

import { toBoolean } from '@utils/bool';

export default {
    name: 'FieldSelect',

    props: {
        label: {
            type: String,
            default: '',
        },

        instructions: {
            type: String,
            default: '',
        },

        id: {
            type: String,
            default: '',
        },

        name: {
            type: String,
            default: '',
        },

        modelValue: {
            type: String,
            default: '',
        },
    },

    data() {
        return {
            error: false,
            errorMessage: '',
            loading: false,
            proxyValue: '',
        };
    },

    created() {
        this.proxyValue = this.modelValue || '';
    },

    methods: {
        getFieldOptions() {
            const fields = this.$store.getters['form/fields'];

            const customFields = [];

            fields.forEach((field) => {
                // Exclude cosmetic fields (with no value)
                if (field.isCosmetic) {
                    return;
                }

                // If this field is nested itself, don't show. The outer field takes care of that below
                if (!toBoolean(field.isNested)) {
                    // Don't show a nested field on its own
                    customFields.push({ label: truncate(field.label, { length: 60 }), value: `{${field.handle}}` });

                    if (field.subfieldOptions && field.hasSubfields) {
                        field.subfieldOptions.forEach((subfield) => {
                            customFields.push({
                                label: `${truncate(field.label, { length: 60 })}: ${truncate(subfield.label, { length: 60 })}`,
                                value: `{${field.handle}[${subfield.handle}]}`,
                            });
                        });
                    }

                    // Is this a repeater or field that supports nesting?
                    if (toBoolean(field.supportsNested) && field.rows) {
                        field.rows.forEach((row) => {
                            row.fields.forEach((subfield) => {
                                customFields.push({
                                    label: `${truncate(field.label, { length: 60 })}: ${truncate(subfield.label, { length: 60 })}`,
                                    value: `{${field.handle}[${subfield.handle}]}`,
                                });

                                if (subfield.subfieldOptions && subfield.hasSubfields) {
                                    subfield.subfieldOptions.forEach((subsubfield) => {
                                        customFields.push({
                                            label: `${truncate(field.label, { length: 60 })}: ${truncate(subfield.label, { length: 60 })}: ${truncate(subsubfield.label, { length: 60 })}`,
                                            value: `{${field.handle}[${subfield.handle}[${subsubfield.handle}]]}`,
                                        });
                                    });
                                }
                            });
                        });
                    }
                }
            });

            return customFields;
        },
    },
};

</script>
