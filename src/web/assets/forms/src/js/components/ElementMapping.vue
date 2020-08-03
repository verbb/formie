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
                        <th scope="col" class="singleline-cell textual">{{ 'Entry Field' | t('formie') }}</th>
                        <th scope="col" class="select-cell">{{ 'Form Field' | t('formie') }}</th>
                    </tr>
                </thead> 

                <tbody>
                    <tr v-for="(row, index) in proxyRows" :key="index" data-id="0">
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
    name: 'ElementMapping',

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

        refreshListener: {
            type: String,
            default: '',
        },

        refreshAction: {
            type: String,
            default: '',
        },

        rows: {
            type: Array,
            default: () => [],
        },

        value: {
            type: Object,
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
            proxyRows: [],
            proxyValue: {},
        };
    },

    created() {
        this.proxyRows = this.rows;
        this.proxyValue = this.value;

        // Prepare an empty model, for new integrations
        if (!Object.keys(this.proxyValue).length) {
            this.resetValues();
        }

        if (this.refreshListener) {
            this.$events.$on(this.refreshListener, (payload) => {
                this.loading = true;
                this.error = false;
                this.errorMessage = '';

                this.$axios.post(Craft.getActionUrl(this.refreshAction), payload).then((response) => {
                    this.loading = false;

                    if (response.data.error) {
                        this.error = true;

                        this.errorMessage = this.$options.filters.t('An error occurred.', 'formie');
                    
                        if (response.data.error) {
                            this.errorMessage += '<br><br><code>' + response.data.error + '</code>';
                        }

                        return;
                    }

                    this.proxyRows = response.data;
                    this.resetValues();
                }).catch(error => {
                    this.loading = false;
                    this.error = true;

                    this.errorMessage = error;
                    
                    if (error.response.data.error) {
                        this.errorMessage += '<br><br><code>' + error.response.data.error + '</code>';
                    }
                });
            });
        }
    },

    methods: {
        resetValues() {
            this.rows.forEach((row) => {
                this.proxyValue[row.handle] = '';
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

<style lang="scss">

.fui-element-mapping {
    position: relative;
}

.fui-element-mapping .fui-error-pane,
.fui-element-mapping .fui-loading-pane {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    z-index: 1;
    background: rgba(255, 255, 255, 0.7);
}

.fui-element-mapping .fui-loading {
    position: absolute;
    top: 50%;
    left: 50%;
    background: #fff;
}

.fui-element-mapping .fui-error-pane {
    align-items: center;
    justify-content: center;
    display: flex;

    [data-icon] {
        display: block;
        font-size: 3em;
        margin-bottom: 0.5rem;
    }
}

.fui-element-mapping .fui-error-content {
    text-align: center;
    max-width: 35rem;
}

</style>
