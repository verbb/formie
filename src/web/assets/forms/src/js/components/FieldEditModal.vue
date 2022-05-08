<template>
    <component :is="'div'">
        <modal ref="modal" modal-class="fui-edit-field-modal" :is-visible="visible" @close="onCancel">
            <template slot="header">
                <h3 class="fui-modal-title">{{ 'Edit Field' | t('formie') }}</h3>
                <div v-if="showFieldType" class="fui-modal-fieldtype">{{ fieldtype.label }}</div>

                <div class="fui-dialog-close" @click.prevent="onCancel"></div>
            </template>

            <template slot="body">
                <div v-if="field.isSynced" class="fui-notice-wrap">
                    <div class="fui-notice warning">
                        <span class="warning with-icon"></span>
                        {{ 'Warning: Currently editing synced field. Changes to this field will be applied to all instances of this field.' | t('formie') }}
                    </div>
                </div>

                <tabs style="height: 100%;">
                    <div class="fui-tabs fui-field-tabs fui-field-tab-list">
                        <tab-list class="fui-pages-menu">
                            <tab v-for="(tab, index) in tabsSchema" :key="index" :class="[ 'fui-tab-item', tabErrorClass(tab.label) ]">
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

                    <div v-if="!loaded" class="fui-loading fui-loading-lg" style="height: 80%;"></div>

                    <formulate-form
                        v-if="loaded"
                        ref="fieldForm"
                        v-model="fieldSettings"
                        :schema="fieldsSchema"
                        :errors="fieldErrors"
                        @submit="submitHandler"
                        @validation="validateForm"
                    />
                </tabs>
            </template>

            <template slot="footer">
                <div v-if="canDelete" class="buttons left">
                    <div class="btn delete" role="button" @click.prevent="deleteField">{{ 'Delete' | t('app') }}</div>
                </div>

                <div class="buttons right">
                    <div class="btn" role="button" @click.prevent="onCancel">{{ 'Cancel' | t('app') }}</div>
                    <div class="btn submit" role="button" @click.prevent="onSave">{{ 'Apply' | t('app') }}</div>
                </div>
            </template>
        </modal>
    </component>
</template>

<script>
// import Vue from 'vue';
import { mapState } from 'vuex';

import Modal from './Modal.vue';

export default {
    name: 'FieldEditModal',

    components: {
        Modal,
    },

    props: {
        visible: {
            type: Boolean,
            default: false,
        },

        canDelete: {
            type: Boolean,
            default: true,
        },

        showFieldType: {
            type: Boolean,
            default: true,
        },

        fieldRef: {
            type: Object,
            default: () => {},
        },

        field: {
            type: Object,
            default: () => {},
        },

        tabsSchema: {
            type: Array,
            default: () => [],
        },

        fieldsSchema: {
            type: Array,
            default: () => [],
        },
    },

    data() {
        return {
            originalField: null,
            loaded: false,
            submitClicked: false,
            tabsWithErrors: [],
        };
    },

    computed: {
        fieldErrors() {
            // Formulate can't handle an empty array
            if (Array.isArray(this.field.errors)) {
                return false;
            }

            return this.field.errors;
        },

        fieldtype() {
            return this.$store.getters['fieldtypes/fieldtype'](this.field.type);
        },

        getFirstError() {
            if (this.fieldErrors) {
                return this.fieldErrors[Object.keys(this.fieldErrors)[0]][0] || '';
            }

            return null;
        },

        fieldSettings: {
            get() {
                return this.field.settings;
            },

            set(fieldSettings) {
                if (!this.fieldRef.submitButton) {
                    // We still use label/handle at the top level, so copy those over
                    this.field.label = fieldSettings.label;
                    this.field.handle = fieldSettings.handle;

                }

                // Update the field settings as 'normal'
                this.field.settings = fieldSettings;
            },
        },
    },

    created() {
        // Store this so we can cancel changes.
        this.originalField = clone(this.field);

        // We need to copy label/handle so Formulate can handle things
        this.fieldSettings.label = this.field.label;
        this.fieldSettings.handle = this.field.handle;

        // Add this to the global Vue instance so we can access it inside fields
        // Could possibly swap this out with Vuex. Currently not a way to inject
        // props into Formulate inputs (other than simple values)
        Vue.prototype.$editingField = this.fieldRef;
    },

    mounted() {
        setTimeout(() => {
            this.loaded = true;

            this.$nextTick().then(() => {
                const $firstText = this.$refs.fieldForm.$el.querySelector('input[type="text"]');

                if ($firstText) {
                    $firstText.focus();
                }
            });
        }, 50);
    },

    destroy() {
        this.destroy();
    },

    methods: {
        destroy() {
            Vue.prototype.$editingField = null;
        },

        hideModal() {
            this.$emit('close');

            this.destroy();
        },

        deleteField() {
            this.$emit('delete');

            this.destroy();
        },

        tabErrorClass(tab) {
            return (this.tabsWithErrors.includes(tab)) ? 'error' : false;
        },

        eachInput(children, callback) {
            children.forEach(child => {
                if (child.$options.name === 'FormulateInput') {
                    callback(child);
                }

                if (child.$children.length) {
                    this.eachInput(child.$children, callback);
                }
            });
        },

        validateForm(error) {
            // Don't show the errors on tabs until we've hit submit
            if (!this.submitClicked) {
                return;
            }

            // Update any tabs with errors as soon as a field is invalid
            this.tabsSchema.forEach(tab => {
                if (tab.fields.includes(error.name)) {
                    if (error.hasErrors) {
                        if (!this.tabsWithErrors.includes(tab.label)) {
                            this.tabsWithErrors.push(tab.label);
                        }
                    } else {
                        const index = this.tabsWithErrors.indexOf(tab.label);

                        if (index > -1) {
                            this.tabsWithErrors.splice(index, 1);
                        }
                    }
                }
            });
        },

        submitHandler() {
            // Validation has already cleared the form

            // Update the state of Vuex to mark the field as no longer brand-new
            this.fieldRef.markAsSaved();

            // Hide the modal
            this.hideModal();
        },

        onCancel() {
            this.$events.emit('fieldEdit.beforeCancel', this.field);

            // Restore original state and exit
            Object.assign(this.field, this.originalField);

            this.$events.emit('fieldEdit.afterCancel', this.field);

            this.$emit('cancel');

            return this.hideModal();
        },

        onSave() {
            this.submitClicked = true;

            // There's checks in Formulate not to fire the `validation` event unless the messages
            // have changed. But, in our case, we always want to fire this when the submit button is pressed
            // so we have to dive into every input and reset the errors. This will then trigger the `@validation`
            // event on the form, and we can show updated error status.
            this.eachInput(this.$refs.fieldForm.$children, (child) => {
                child.validationErrors = [];
            });

            // Validate the form - this will prevent firing `submitHandler()` if it fails
            this.$refs.fieldForm.formSubmitted();
        },
    },
};

</script>
