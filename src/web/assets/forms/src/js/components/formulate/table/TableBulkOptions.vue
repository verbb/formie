<template>
    <div>
        <modal ref="modal" modal-class="fui-table-bulk-add-modal" :is-visible="visible" @close="onCancel">
            <template slot="header">
                <h3 class="fui-modal-title">{{ 'Bulk Add Options' | t('formie') }}</h3>

                <div class="fui-dialog-close" @click.prevent="onCancel"></div>
            </template>

            <template slot="body">
                <div class="fui-modal-content">
                    <div class="fui-field-instructions" style="margin: -10px 0 15px;">
                        <p>{{ 'Select from predefined options and customize or paste your own to bulk add options.' | t('formie') }}</p>
                    </div>

                    <FormulateForm ref="fieldForm" @submit="submitHandler">
                        <div class="fui-row">
                            <div class="fui-col-6">
                                <FormulateInput
                                    type="select"
                                    :label="$options.filters.t('Predefined Options', 'formie')"
                                    :help="$options.filters.t('Select from the available predefined options.', 'formie')"
                                    :options="predefinedOptions"
                                    @change="onPredefinedChange"
                                />

                                <div v-if="loading" class="fui-loading-pane">
                                    <div class="fui-loading fui-loading-lg"></div>
                                </div>

                                <div v-if="error" class="fui-error-pane error">
                                    <div class="fui-error-content">
                                        <span data-icon="alert"></span>

                                        <span class="error" v-html="errorMessage"></span>
                                    </div>
                                </div>

                                <div v-if="!loading">
                                    <FormulateInput
                                        v-if="labelOptions.length"
                                        v-model="labelOption"
                                        type="select"
                                        :label="$options.filters.t('Option Label', 'formie')"
                                        :help="$options.filters.t('Select the data to be used as the option label.', 'formie')"
                                        :options="labelOptions"
                                        @change="onLabelChange"
                                    />

                                    <FormulateInput
                                        v-if="valueOptions.length"
                                        v-model="valueOption"
                                        type="select"
                                        :label="$options.filters.t('Option Value', 'formie')"
                                        :help="$options.filters.t('Select the data to be used as the option value.', 'formie')"
                                        :options="valueOptions"
                                        @change="onValueChange"
                                    />
                                </div>
                            </div>

                            <div class="fui-col-6">
                                <textarea
                                    v-model="preview"
                                    class="text fui-table-bulk-preview"
                                ></textarea>
                            </div>
                        </div>
                    </FormulateForm>
                </div>
            </template>

            <template slot="footer">
                <div class="buttons right">
                    <div class="btn" role="button" @click.prevent="onCancel">{{ 'Cancel' | t('app') }}</div>
                    <div class="btn submit" :class="{ 'fui-loading fui-loading-sm': saveLoading }" role="button" @click.prevent="onSave">{{ 'Add Options' | t('app') }}</div>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import Modal from '../../Modal.vue';

export default {
    name: 'TableBulkOptions',

    components: {
        Modal,
    },

    props: {
        visible: {
            type: Boolean,
            default: false,
        },

        predefinedOptions: {
            type: Array,
            default: () => [],
        },

        tableField: {
            type: Object,
            default: () => {},
        },
    },

    data() {
        return {
            success: false,
            error: false,
            errorMessage: '',
            loading: false,
            saveLoading: false,

            options: [],
            availableOptions: [],
            labelOptions: [],
            valueOptions: [],
            predefinedOption: null,
            labelOption: null,
            valueOption: null,
            preview: '',
        };
    },

    watch: {
        predefinedOption() {
            this.updatePreview();
        },

        labelOption() {
            this.updatePreview();
        },

        valueOption() {
            this.updatePreview();
        },
    },

    methods: {
        onCancel() {
            this.$emit('cancel');
            this.$emit('close');
        },

        onSave() {
            // Validate the form - this will prevent firing `submitHandler()` if it fails
            this.$refs.fieldForm.formSubmitted();
        },

        submitHandler() {
            this.saveLoading = true;

            // Give it a sec for large data sets to update UI
            setTimeout(() => {
                // Prepare our options to set
                const values = this.preview.split('\n').map(line => {
                    const lineValues = line.split('|');

                    return { label: lineValues[0], value: lineValues[1] || lineValues[0], isDefault: false };
                });

                this.tableField.setItems(values, false);

                this.$emit('close');

                this.saveLoading = false;
            }, 100);
        },

        onPredefinedChange(e) {
            this.predefinedOption = e.target.value;
            this.fetchOptions({ option: this.predefinedOption });
        },

        onLabelChange(e) {
            this.labelOption = e.target.value;
        },

        onValueChange(e) {
            this.valueOption = e.target.value;
        },

        updatePreview() {
            if (this.availableOptions.length) {
                var options = [];

                options = this.availableOptions.map(option => {
                    if (this.labelOption || this.valueOption) {
                        if (this.labelOption === this.valueOption) {
                            return option[this.labelOption];
                        } else {
                            return option[this.labelOption] + '|' + option[this.valueOption];
                        }
                    } else {
                        return option;
                    }
                });

                this.preview = options.join('\n');
            }
        },

        fetchOptions(payload = {}) {
            this.success = false;
            this.error = false;
            this.errorMessage = '';
            this.loading = true;

            this.$axios.post(Craft.getActionUrl('formie/fields/get-predefined-options'), payload).then((response) => {
                this.loading = false;

                if (response.data.error) {
                    this.error = true;

                    this.errorMessage = this.$options.filters.t('An error occurred.', 'formie');
                
                    if (response.data.error) {
                        this.errorMessage += '<br><code>' + response.data.error + '</code>';
                    }

                    return;
                }

                if (response.data) {
                    this.availableOptions = response.data.data || [];
                    this.labelOptions = response.data.labelOptions || [];
                    this.valueOptions = response.data.valueOptions || [];
                    this.labelOption = response.data.labelOption || null;
                    this.valueOption = response.data.valueOption || null;
                }

                this.updatePreview();

                this.success = true;
            }).catch(error => {
                this.loading = false;
                this.error = true;

                this.errorMessage = error;
                
                if (error.response.data.error) {
                    this.errorMessage += '<br><code>' + error.response.data.error + '</code>';
                }
            });
        },
    },
};

</script>

<style lang="scss">

.fui-table-bulk-add-modal {
    width: 60%;
    height: 50%;
}

.fui-table-bulk-preview {
    border-radius: 4px;
    width: 100%;
    height: 210px;
    padding: 5px !important;
    font-size: 12px !important;
    line-height: 1.4 !important;
}

</style>
