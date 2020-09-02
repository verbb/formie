<template>
    <component :is="'div'">
        <modal ref="modal" modal-class="fui-edit-pages-modal" :is-visible="visible" @close="onModalCancel">
            <template slot="header">
                <h3 class="fui-modal-title">{{ 'Edit Pages' | t('formie') }}</h3>

                <div class="fui-dialog-close" @click.prevent="onModalCancel"></div>
            </template>

            <template slot="body">
                <FormulateForm ref="fieldForm" v-model="formValues" @submit="submitHandler">
                    <table-block
                        name="pages"
                        new-row-label="Add new page"
                        :show-header="false"
                        :allow-multiple-default="true"
                        :confirm-delete="true"
                        :confirm-message="confirmMessage"
                        :new-row-defaults="newRowDefaults"
                        validation="min:1,length|requiredLabels"
                        :columns="[{
                            type: 'label',
                            label: $options.filters.t('Pages', 'formie'),
                            class: 'singleline-cell textual',
                        }]"
                    />
                </FormulateForm>
            </template>

            <template slot="footer">
                <div class="buttons right">
                    <div class="btn" role="button" @click.prevent="onModalCancel">{{ 'Close' | t('app') }}</div>
                    <div class="btn submit" role="button" @click.prevent="savePages">{{ 'Apply' | t('app') }}</div>
                    <div class="spinner hidden"></div>
                </div>
            </template>
        </modal>
    </component>
</template>

<script>
import { mapState } from 'vuex';
import { newId } from '../utils/string';

import Modal from './Modal.vue';

export default {
    name: 'FieldPageModal',

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
            originalPages: null,
        };
    },

    computed: {
        ...mapState({
            pages: state => state.form.pages,
        }),

        formValues: {
            get() {
                return {
                    pages: this.pages,
                };
            },

            set(values) {
                this.$emit('input', values.pages);
            },
        },
    },

    created() {
        // Store this so we can cancel changes.
        this.originalPages = clone(this.pages);
    },

    methods: {
        onModalCancel() {
            // Restore original state and exit
            Vue.set(this.$store.state.form, 'pages', this.originalPages);

            this.$emit('close');
        },

        confirmMessage(row) {
            const confirmationMessage = Craft.t('formie', 'Are you sure you want to delete “{name}”? This will also delete all fields on this page, and cannot be undone.', { name: row.label });

            return Craft.escapeHtml(confirmationMessage);
        },

        newRowDefaults() {
            return {
                id: newId(),
                label: Craft.t('formie', 'New Page'),
                rows: [],
            };
        },

        submitHandler() {
            this.$emit('close');
        },

        savePages() {
            // Validate the form - this will prevent firing `submitHandler()` if it fails
            this.$refs.fieldForm.formSubmitted();
        },
    },
};

</script>

<style lang="scss">

.fui-edit-pages-modal.fui-modal {
    width: 30%;
    min-height: 300px;
}

.fui-edit-pages-modal .field[data-type="table"] {
    padding: 20px;
}

</style>
