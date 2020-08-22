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
                        <th scope="col" class="singleline-cell textual">{{ '{name} Field' | t('formie', { name: nameLabel }) }}</th>
                        <th scope="col" class="select-cell">{{ 'Form Field' | t('formie') }}</th>
                    </tr>
                </thead> 

                <tbody>
                    <tr v-if="rows.length === 0">
                        <td colspan="2">
                            <div class="zilch">
                                {{ 'No fields available.' | t('formie') }}
                            </div>
                        </td>
                    </tr>
                    <tr v-for="(row, index) in rows" v-else :key="index" data-id="0">
                        <td class="singleline-cell textual" style="width: 50%;">
                            <textarea v-model="row.name" rows="1" style="min-height: 36px;" readonly></textarea>
                        </td>

                        <td class="select-cell" style="width: 50%;">
                            <div class="flex flex-nowrap">
                                <div class="select small">
                                    <select v-model="proxyValue[row.handle]" :name="name + '[' + row.handle + ']'">
                                        <option value="">{{ 'Don’t Include' | t('formie') }}</option>

                                        <optgroup v-for="(optgroup, i) in getFieldOptions()" :key="i" :label="optgroup.label">
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
            default: () => [],
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

        getFieldOptions() {
            var fields = this.$store.getters['form/fields'];

            var options = [
                {
                    label: Craft.t('formie', 'Submission'),
                    options: [
                        { label: Craft.t('formie', 'Title'), value: '{title}' },
                        { label: Craft.t('formie', 'ID'), value: '{id}' },
                    ],
                },
                {
                    label: Craft.t('formie', 'Fields'),
                    options: fields.map(field => {
                        return { label: field.label, value: '{' + field.handle + '}' };
                    }),
                },
            ];

            return options;
        },
    },
};

</script>
