<template>
    <div>
        <modal ref="modal" modal-class="fui-edit-table-modal" :is-visible="visible" :show-header="false" @close="onCancel">
            <template slot="header"></template>

            <template slot="body">
                <div class="fui-modal-content">
                    <FormulateForm ref="fieldForm" v-model="formValues" @submit="submitHandler">
                        <table-block
                            name="options"
                            :label="$options.filters.t('Dropdown Options', 'formie')"
                            :help="$options.filters.t('Define the available options.', 'formie')"
                            validation="min:1,length|uniqueLabels|uniqueValues|requiredLabels|requiredValues"
                            :new-row-defaults="{
                                label: '',
                                value: '',
                                isOptgroup: false,
                                isDefault: false,
                            }"
                            :columns="[{
                                type: 'label',
                                label: $options.filters.t('Option Label', 'formie'),
                                class: 'singleline-cell textual',
                            }, {
                                type: 'value',
                                label: $options.filters.t('Value', 'formie'),
                                class: 'code singleline-cell textual',
                            }, {
                                type: 'default',
                                name: 'default',
                                label: $options.filters.t('Default?', 'formie'),
                                class: 'thin checkbox-cell',
                            }]"
                        />
                    </FormulateForm>
                </div>
            </template>

            <template slot="footer">
                <div class="buttons right">
                    <div class="btn submit" role="button" @click.prevent="onSave">{{ 'Done' | t('app') }}</div>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import Modal from '../../Modal.vue';

export default {
    name: 'TableDropdown',

    components: {
        Modal,
    },

    props: {
        visible: {
            type: Boolean,
            default: false,
        },
    },

    data() {
        return {
            originalValues: null,
        };
    },

    computed: {
        formValues: {
            get() {
                if (this.$parent.model.options) {
                    return { options: this.$parent.model.options };
                }

                return { options: [] };
            },

            set(newValue) {
                newValue.options.forEach((item, index) => {
                    this.$parent.model.options[index] = item;
                });
            },
        },
    },

    created() {
        // Store this so we can cancel changes.
        this.originalValues = clone(this.formValues);
    },

    methods: {
        onCancel() {
            // Restore original state and exit
            this.$parent.model.options = this.originalValues.options;
            
            this.$emit('cancel');
            this.$emit('close');
        },

        onSave() {
            // Validate the form - this will prevent firing `submitHandler()` if it fails
            this.$refs.fieldForm.formSubmitted();
        },

        submitHandler() {
            this.$emit('close');
        },
    },
};

</script>

<style lang="scss">

.fui-edit-table-modal {
    width: 35%;
    height: 65%;
    min-height: 200px;
    min-width: 200px;
}

.fui-edit-table-modal .fui-modal-content {
    padding-bottom: 6rem;
}

</style>
