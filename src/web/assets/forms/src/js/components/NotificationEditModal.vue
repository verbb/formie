<template>
    <component :is="'div'">
        <modal ref="modal" to="notification-modals" modal-class="fui-edit-notification-modal" :is-visible="visible" @close="onCancel">
            <template slot="header">
                <h3 class="fui-modal-title">{{ 'Edit Notification' | t('formie') }}</h3>

                <div class="fui-dialog-close" @click.prevent="onCancel"></div>
            </template>

            <template slot="body">
                <tabs>
                    <div class="fui-tabs fui-field-tabs fui-field-tab-list">
                        <tab-list class="fui-pages-menu">
                            <tab v-for="(tab, index) in tabsSchema" :key="index" :class="[ 'fui-tab-item', tabErrorClass(tab.label) ]">
                                {{ tab.label }}
                            </tab>
                        </tab-list>
                    </div>

                    <FormulateForm
                        ref="fieldForm"
                        v-model="proxy"
                        :notification="proxy"
                        :schema="fieldsSchema"
                        @submit="submitHandler"
                        @validation="validateForm"
                    />
                </tabs>
            </template>

            <template slot="footer">
                <div v-if="!notificationRef.isNew" class="buttons left">
                    <div class="spinner" :class="{ hidden: !deleteLoading }"></div>
                    <div class="btn delete" role="button" @click.prevent="deleteNotification">{{ 'Delete' | t('app') }}</div>
                </div>

                <div class="buttons right">
                    <div class="btn" role="button" @click.prevent="onCancel">{{ 'Cancel' | t('app') }}</div>
                    <div class="btn submit" role="button" @click.prevent="onSave">{{ 'Apply' | t('app') }}</div>
                    <div class="spinner" :class="{ hidden: !saveLoading }"></div>
                </div>
            </template>
        </modal>
    </component>
</template>

<script>
import cloneDeep from 'lodash/cloneDeep';

import Modal from './Modal.vue';

export default {
    name: 'NotificationEditModal',

    components: {
        Modal,
    },

    props: {
        visible: {
            type: Boolean,
            default: false,
        },

        label: {
            type: String,
            default: '',
        },

        notification: {
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
            saveLoading: false,
            deleteLoading: false,
            originalNotification: null,
            submitClicked: false,
            tabsWithErrors: [],
        };
    },

    computed: {
        proxy: {
            get() {
                return this.notification;
            },

            set(notification) {
                Object.assign(this.notification, notification);
            },
        },

        notificationRef() {
            return this.$parent;
        },
    },

    watch: {
        proxy: {
            deep: true,
            handler(newVal) {
                this.$emit('input', newVal);
            },
        },
    },

    beforeCreate() {
        this.pages = this.$parent.$parent.tabs;
    },

    created() {
        // Store this so we can cancel changes.
        this.originalNotification = cloneDeep(this.notification);
    },

    methods: {
        hideModal() {
            this.$emit('close');
        },

        deleteNotification() {
            this.$emit('delete');
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

            this.notificationRef.addNotification();

            // Hide the modal
            this.hideModal();
        },

        onCancel() {
            // Restore original state and exit
            Object.assign(this.proxy, this.originalNotification);

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
