<template>
    <modal ref="modal" v-model="showModal" modal-class="fui-edit-table-modal" :show-header="false">
        <template #header></template>

        <template #body>
            <div class="fui-modal-content">
                <FormKitForm ref="fieldForm" v-model="formValues" name="tableDropdownOptions" @submit="submitHandler">
                    <FormKit
                        type="table"
                        name="options"
                        :label="t('formie', 'Dropdown Options')"
                        :help="t('formie', 'Define the available options.')"
                        validation="+min:1|uniqueTableCellLabel|uniqueTableCellValue|requiredTableCellLabel|requiredTableCellValue"
                        :new-row-defaults="{
                            label: '',
                            value: '',
                            isOptgroup: false,
                            isDefault: false,
                        }"
                        :columns="[{
                            type: 'label',
                            label: t('formie', 'Option Label'),
                            class: 'singleline-cell textual',
                        }, {
                            type: 'value',
                            label: t('formie', 'Value'),
                            class: 'code singleline-cell textual',
                        }, {
                            type: 'default',
                            name: 'default',
                            label: t('formie', 'Default'),
                            class: 'thin checkbox-cell',
                        }]"
                    />
                </FormKitForm>
            </div>
        </template>

        <template #footer>
            <div class="buttons right">
                <div class="btn submit" role="button" @click.prevent="onSave">{{ t('app', 'Done') }}</div>
            </div>
        </template>
    </modal>
</template>

<script>
import Modal from '@components/Modal.vue';

export default {
    name: 'TableDropdown',

    components: {
        Modal,
    },

    props: {
        showModal: {
            type: Boolean,
            default: false,
        },

        values: {
            type: Object,
            default: () => {},
        },
    },

    data() {
        return {
            originalValues: null,
            formValues: this.values,
        };
    },

    created() {
        // Store this so we can cancel changes.
        this.originalValues = this.clone(this.values);
    },

    methods: {
        closeModal() {
            // Close the modal programatically, which will fire `@closed`
            this.$refs.modal.close();
        },

        onCancelModal() {
            // Restore original state and exit
            this.$parent.model.options = this.originalValues.options || [];

            this.closeModal();
        },

        onSave() {
            // Validate the form - this will prevent firing `submitHandler()` if it fails
            this.$refs.fieldForm.submit();
        },

        submitHandler() {
            this.closeModal();
        },
    },
};

</script>

<style lang="scss">

.fui-edit-table-modal .fui-modal-wrap {
    width: 35%;
    height: 65%;
    min-height: 200px;
    min-width: 200px;
}

.fui-edit-table-modal .fui-modal-content {
    padding-bottom: 6rem;
}

</style>
