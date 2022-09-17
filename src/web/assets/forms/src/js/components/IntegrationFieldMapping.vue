<template>
    <div class="field">
        <div class="heading">
            <label :id="id + '-label'" :for="id">{{ label }}</label>

            <div class="instructions">
                <p>{{ instructions }}</p>
            </div>
        </div>

        <div class="fui-element-mapping input ltr">
            <input type="hidden" :name="name" value="">

            <div v-if="loading" class="fui-loading-pane">
                <div class="fui-loading fui-loading-lg"></div>
            </div>

            <div v-if="error" class="fui-error-pane error">
                <div class="fui-error-content">
                    <span data-icon="alert"></span>

                    <span class="error" v-html="errorMessage"></span>
                </div>
            </div>

            <table :id="id" class="editable fullwidth">
                <thead>
                    <tr>
                        <th scope="col" class="singleline-cell textual">{{ t('formie', '{name} Field', { name: nameLabel }) }}</th>
                        <th scope="col" class="select-cell">{{ t('formie', 'Form Field') }}</th>
                    </tr>
                </thead>

                <tbody>
                    <tr v-if="rows.length === 0">
                        <td colspan="2">
                            <div class="zilch">
                                {{ t('formie', 'No fields available.') }}
                            </div>
                        </td>
                    </tr>

                    <tr v-for="(row, index) in rows" v-else :key="index" data-id="0">
                        <td class="singleline-cell textual" style="width: 50%;">
                            <span class="fui-table-label" :class="{ 'required': row.required }">{{ row.name }}</span>
                        </td>

                        <td class="select-cell" style="width: 50%;">
                            <div class="flex flex-nowrap">
                                <div class="select small">
                                    <select v-model="proxyValue[row.handle]" :name="name + '[' + row.handle + ']'">
                                        <option value="">{{ t('formie', 'Don’t Include') }}</option>

                                        <optgroup v-for="(optgroup, i) in getFieldOptions(row.options)" :key="i" :label="optgroup.label">
                                            <option v-for="(option, j) in optgroup.options" :key="j" :value="option.value">
                                                {{ option.label }}
                                            </option>
                                        </optgroup>
                                    </select>
                                </div>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
import { truncate, isEmpty } from 'lodash-es';
import { toBoolean } from '@utils/bool';

export default {
    name: 'IntegrationFieldMapping',

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

        nameLabel: {
            type: String,
            default: '',
        },

        rows: {
            type: Array,
            default: () => { return []; },
        },

        value: {
            type: [Object, String],
            default: () => {
                return {};
            },
        },
    },

    data() {
        return {
            error: false,
            errorMessage: '',
            loading: false,
            proxyValue: {},
        };
    },

    watch: {
        rows(newValue) {
            // Each time we modify the rows, be sure to populate the value,
            // even with empty data so the dropdowns aren't blank...
            this.resetValues();
        },
    },

    created() {
        this.proxyValue = this.value;

        if (!this.proxyValue) {
            this.proxyValue = {};
        }

        // Prepare an empty model, for new integrations
        if (!Object.keys(this.proxyValue).length) {
            this.resetValues();
        }
    },

    methods: {
        resetValues() {
            this.rows.forEach((row) => {
                if (!this.proxyValue[row.handle]) {
                    this.proxyValue[row.handle] = '';
                }
            });
        },

        getFieldOptions(providerOptions) {
            const fields = this.$store.getters['form/fields'];

            const options = [];

            if (!isEmpty(providerOptions)) {
                options.push(providerOptions);
            }

            options.push({
                label: Craft.t('formie', 'Submission'),
                options: [
                    { label: Craft.t('formie', 'Title'), value: '{submission:title}' },
                    { label: Craft.t('formie', 'ID'), value: '{submission:id}' },
                    { label: Craft.t('formie', 'Form Name'), value: '{submission:formName}' },
                ],
            });

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

            options.push({
                label: Craft.t('formie', 'Fields'),
                options: customFields,
            });

            return options;
        },
    },
};

</script>

<style lang="scss">

.fui-table-label {
    min-height: 34px;
    display: flex;
    align-items: center;
    margin: 0 10px;
    text-align: left;
}

</style>
