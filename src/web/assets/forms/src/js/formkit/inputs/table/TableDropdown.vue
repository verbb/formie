<template>
    <div>
        <a class="settings light" role="button" data-icon="settings" @click.prevent="openModal"></a>

        <modal ref="modal" :model-value="showModal" modal-class="fui-edit-table-modal" :show-header="false" @update:model-value="showModal = $event">
            <template #header></template>

            <template #body>
                <div class="fui-modal-content">
                    <FormKitForm ref="fieldForm" v-model="formValues" ignore="true" @submit="submitHandler">
                        <FormKit
                            ref="tableField"
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
                    <button class="btn submit" role="button" @click.prevent="onSave">{{ t('app', 'Done') }}</button>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import Modal from '@components/Modal.vue';

export default {
    name: 'TableDropdown',

    components: {
        Modal,
    },

    props: {
        values: {
            type: Object,
            default: () => {},
        },
    },

    emits: ['update:values'],

    data() {
        return {
            showModal: false,
            formValues: null,
        };
    },

    created() {
        this.formValues = this.clone(this.values);
    },

    methods: {
        openModal() {
            this.showModal = true;
        },

        closeModal() {
            this.showModal = false;
        },

        onCancelModal() {
            this.closeModal();
        },

        onSave() {
            // Validate the form - this will prevent firing `submitHandler()` if it fails
            this.$refs.fieldForm.submit();
        },

        submitHandler() {
            this.closeModal();

            this.$emit('update:values', this.clone(this.formValues));
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
