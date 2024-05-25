<template>
    <modal ref="modal" :model-value="showModal" modal-class="fui-edit-field-modal" @update:model-value="showModal = $event" @click-outside="onCancelModal">
        <template #header>
            <h3 class="fui-modal-title">{{ t('formie', 'Edit Field') }}</h3>
            <div v-if="showFieldType" class="fui-modal-fieldtype">{{ fieldtype.label }}</div>

            <button class="fui-dialog-close" @click.prevent="onCancelModal"></button>
        </template>

        <template #body>
            <div v-if="field.isSynced" class="fui-notice-wrap">
                <div class="fui-notice warning">
                    <span class="warning with-icon"></span>
                    {{ t('formie', 'Warning: Currently editing synced field. Changes to this field will be applied to all instances of this field.') }}
                </div>
            </div>

            <tabs style="height: 100%;">
                <div class="fui-tabs fui-field-tabs fui-field-tab-list">
                    <tab-list class="fui-pages-menu">
                        <tab v-for="(tab, index) in tabsSchema" :key="index" :index="index" :class="[ 'fui-tab-item', tabErrorClass(tab.label) ]">
                            {{ tab.label }}
                        </tab>
                    </tab-list>
                </div>

                <div v-if="getFirstError" class="fui-notice-wrap">
                    <div class="fui-notice error">
                        <span class="error with-icon"></span>
                        {{ getFirstError }}
                    </div>
                </div>

                <div class="fui-modal-content" :style="{ height: (!mounted) ? '80%' : '' }">
                    <div v-if="!mounted" class="fui-loading fui-loading-lg" style="height: 100%;"></div>

                    <FormKitForm
                        v-if="mounted"
                        ref="fieldForm"
                        v-model="fieldSettings"
                        :ignore="ignoreForm"
                        @submit="submitHandler"
                        @submit-raw="submitHandlerRaw"
                    >
                        <FormKitSchema :schema="fieldsSchema" :data="fieldsSchemaData" />
                    </FormKitForm>
                </div>
            </tabs>
        </template>

        <template #footer>
            <div v-if="canDelete" class="buttons left">
                <button class="btn delete" role="button" @click.prevent="deleteField">{{ t('app', 'Delete') }}</button>
            </div>

            <div class="buttons right">
                <button class="btn" role="button" @click.prevent="onCancelModal">{{ t('app', 'Cancel') }}</button>
                <button class="btn submit" role="button" @click.prevent="onSave">{{ t('app', 'Apply') }}</button>
            </div>
        </template>
    </modal>
</template>

<script>
import { isEmpty } from 'lodash-es';
import { mapState } from 'vuex';

// eslint-disable-next-line
import { Tabs, Tab, TabList } from '@vendor/vue-accessible-tabs';

import Modal from '@components/Modal.vue';

export default {
    name: 'FieldEditModal',

    components: {
        Modal,
        Tabs,
        Tab,
        TabList,
    },

    props: {
        canDelete: {
            type: Boolean,
            default: true,
        },

        showFieldType: {
            type: Boolean,
            default: true,
        },

        ignoreForm: {
            type: Boolean,
            default: false,
        },

        isSubField: {
            type: Boolean,
            default: false,
        },

        fieldRef: {
            type: Object,
            default: () => {},
        },

        showModal: {
            type: Boolean,
            default: () => {},
        },

        field: {
            type: Object,
            default: () => {},
        },

        tabsSchema: {
            type: Array,
            default: () => { return []; },
        },

        fieldsSchema: {
            type: Array,
            default: () => { return []; },
        },
    },

    emits: ['delete', 'update:field'],

    data() {
        return {
            originalField: null,
            mounted: false,
            tabsWithErrors: [],
        };
    },

    computed: {
        fieldErrors() {
            return this.field.errors;
        },

        fieldtype() {
            return this.$store.getters['fieldtypes/fieldtype'](this.field.type);
        },

        getFirstError() {
            if (!isEmpty(this.fieldErrors)) {
                return this.fieldErrors[Object.keys(this.fieldErrors)[0]][0] || '';
            }

            return null;
        },

        fieldsSchemaData() {
            let isInGroup = false;
            let isInRepeater = false;
            const { parentField } = this.fieldRef;

            if (parentField) {
                isInGroup = parentField.type === 'verbb\\formie\\fields\\Group';
                isInRepeater = parentField.type === 'verbb\\formie\\fields\\Repeater';
            }

            return {
                hasNestedFields: this.field.hasNestedFields,
                hasSubFields: this.field.hasSubFields,
                isNested: this.field.isNested,
                isSingleNested: this.field.isSingleNested,
                isMultiNested: this.field.isMultiNested,
                fieldtype: this.field.type,
                isInGroup,
                isInRepeater,
            };
        },

        fieldSettings: {
            get() {
                return this.field.settings;
            },

            set(fieldSettings) {
                // Update the field settings as 'normal'
                this.field.settings = fieldSettings;
            },
        },
    },

    created() {
        // Store this so we can cancel changes.
        this.originalField = this.clone(this.field);

        // If this is a subfield, don't handle setting the editing field - at least for now until we can figure out
        // a better system to get the current, top-level field we're editing (handles get generated on both for example)
        if (!this.isSubField) {
            // Add this to the global Vue instance so we can access it inside fields
            this.$store.dispatch('formie/setEditingField', this.fieldRef);
        }
    },

    mounted() {
        // Set a small delay to show the modal, then try to render the form, which can take a little bit
        // for complex settings setups. Likely remove when we can integrate with FormKit's native repeater
        // as the major slowdown is out toggle blocks and tables.
        setTimeout(() => {
            this.mounted = true;

            this.$nextTick().then(() => {
                if (this.$refs.fieldForm) {
                    const $firstText = this.$refs.fieldForm.$el.parentNode.querySelector('input[type="text"]');

                    if ($firstText && $firstText.value.length === 0) {
                        setTimeout(() => {
                            $firstText.focus();
                        }, 200);
                    }

                    // Set any errors on the form, if they exist
                    if (!isEmpty(this.fieldErrors)) {
                        this.$refs.fieldForm.setErrors(this.fieldErrors);

                        // Wait until FormKit has settled
                        setTimeout(() => {
                            this.updateTabs();
                        }, 50);
                    }
                }
            });
        }, 100);
    },

    destroy() {
        this.destroy();
    },

    methods: {
        destroy() {
            // Wait a little for the transition
            setTimeout(() => {
                this.$store.dispatch('formie/setEditingField', null);
            }, 200);
        },

        closeModal() {
            // Close the modal programatically, which will fire `@closed`
            this.$refs.modal.close();

            this.destroy();
        },

        deleteField() {
            this.$emit('delete');

            this.destroy();
        },

        tabErrorClass(tab) {
            return (this.tabsWithErrors.includes(tab)) ? 'error' : false;
        },

        submitHandler() {
            // Validation has already cleared the form

            // Update the state of Vuex to mark the field as no longer brand-new
            if (typeof this.fieldRef.markAsSaved === 'function') {
                this.fieldRef.markAsSaved();
            }

            // Hide the modal
            this.closeModal();

            this.$events.emit('formie:save-field', this.field);
        },

        submitHandlerRaw() {
            // When submitting from the form itself (hitting enter) we need to trigger any extra validation
            // functionality, like showing any errors on tabs.
            this.updateTabs();
        },

        onCancelModal() {
            this.$events.emit('fieldEdit.beforeCancel', this.field);

            // Restore original state and exit
            this.$emit('update:field', this.originalField);

            this.$events.emit('fieldEdit.afterCancel', this.field);

            this.closeModal();
        },

        updateTabs() {
            const errors = this.$refs.fieldForm.getErrors();

            // Reset errors
            this.tabsWithErrors = [];

            // Update any tabs with errors. Just done on submit to prevent too much activity
            this.tabsSchema.forEach((tab) => {
                // Search for an array against an array
                const isInTab = tab.fields.some((v) => { return errors.includes(v); });

                if (isInTab) {
                    this.tabsWithErrors.push(tab.label);
                }
            });
        },

        onSave() {
            // Call any 'raw' submit functions like showing tab errors
            this.submitHandlerRaw();

            // Validate the form - this will prevent firing `submitHandler()` if it fails
            this.$refs.fieldForm.submit();
        },
    },
};

</script>
<style lang="scss">

.fui-edit-subfield-modal {
    .fui-modal-wrap {
        width: 56%;
        height: 56%;
        min-width: 500px;
        min-height: 300px;
    }
}

</style>
