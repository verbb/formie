<template>
        <modal ref="modal" v-model="showModal" modal-class="fui-table-bulk-add-modal" @click-outside="onCancelModal">
            <template v-slot:header>
                <h3 class="fui-modal-title">{{ t('formie', 'Bulk Add Options') }}</h3>

                <div class="fui-dialog-close" @click.prevent="onCancelModal"></div>
            </template>

            <template v-slot:body>
                <div class="fui-modal-content">
                    <div class="fui-field-instructions" style="margin: -10px 0 15px;">
                        <p>{{ t('formie', 'Select from predefined options and customize or paste your own to bulk add options.') }}</p>
                    </div>

                    <FormKitForm ref="fieldForm" @submit="submitHandler">
                        <div class="fui-row">
                            <div class="fui-col-6">
                                <FormKit
                                    type="select"
                                    :label="t('formie', 'Predefined Options')"
                                    :help="t('formie', 'Select from the available predefined options.')"
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
                                    <FormKit
                                        v-if="labelOptions.length"
                                        v-model="labelOption"
                                        type="select"
                                        :label="t('formie', 'Option Label')"
                                        :help="t('formie', 'Select the data to be used as the option label.')"
                                        :options="labelOptions"
                                        @change="onLabelChange"
                                    />

                                    <FormKit
                                        v-if="valueOptions.length"
                                        v-model="valueOption"
                                        type="select"
                                        :label="t('formie', 'Option Value')"
                                        :help="t('formie', 'Select the data to be used as the option value.')"
                                        :options="valueOptions"
                                        @change="onValueChange"
                                    />
                                </div>
                            </div>

                            <div class="fui-col-6">
                                <textarea v-model="preview" class="text fui-table-bulk-preview"></textarea>
                            </div>
                        </div>
                    </FormKitForm>
                </div>
            </template>

            <template v-slot:footer>
                <div class="buttons right">
                    <div class="btn" role="button" @click.prevent="onCancelModal">{{ t('app', 'Cancel') }}</div>
                    <div class="btn submit" :class="{ 'fui-loading fui-loading-sm': saveLoading }" role="button" @click.prevent="onSave">{{ t('app', 'Add Options') }}</div>
                </div>
            </template>
        </modal>
</template>

<script>
import Modal from '@components/Modal.vue';

export default {
    name: 'TableBulkOptions',

    components: {
        Modal,
    },

    props: {
        showModal: {
            type: Boolean,
            default: () => {},
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
        closeModal() {
            // Close the modal programatically, which will fire `@closed`
            this.$refs.modal.close();
        },

        onCancelModal() {
            this.closeModal();
        },

        onSave() {
            // Validate the form - this will prevent firing `submitHandler()` if it fails
            this.$refs.fieldForm.submit();
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

                this.closeModal();

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

        fetchOptions(data = {}) {
            this.success = false;
            this.error = false;
            this.errorMessage = '';
            this.loading = true;

            Craft.sendActionRequest('POST', 'formie/fields/get-predefined-options', { data }).then((response) => {
                this.loading = false;

                if (response.data.error) {
                    this.error = true;

                    this.errorMessage = Craft.t('formie', 'An error occurred.');
                
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

.fui-table-bulk-preview {
    border-radius: 4px;
    width: 100%;
    height: 210px;
    padding: 5px !important;
    font-size: 12px !important;
    line-height: 1.4 !important;
}

</style>
