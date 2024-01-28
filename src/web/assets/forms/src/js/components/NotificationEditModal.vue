<template>
    <modal ref="modal" :model-value="showModal" modal-class="fui-edit-notification-modal" @update:model-value="showModal = $event" @click-outside="onCancelModal">
        <template #header>
            <h3 class="fui-modal-title">{{ t('formie', 'Edit Notification') }}</h3>

            <button class="fui-dialog-close" @click.prevent="onCancelModal"></button>
        </template>

        <template #body>
            <tabs>
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

                    <FormKitForm v-if="mounted" ref="fieldForm" :model-value="notification" @update:model-value="notification = $event" @submit="submitHandler" @submit-raw="submitHandlerRaw">
                        <FormKitSchema :schema="fieldsSchema" />
                    </FormKitForm>
                </div>
            </tabs>
        </template>

        <template #footer>
            <div v-if="!notificationRef.isNew" class="buttons left">
                <button class="btn delete" role="button" @click.prevent="deleteNotification">{{ t('app', 'Delete') }}</button>
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

// eslint-disable-next-line
import { Tabs, Tab, TabList } from '@vendor/vue-accessible-tabs';

import Modal from '@components/Modal.vue';

export default {
    name: 'NotificationEditModal',

    components: {
        Modal,
        Tabs,
        Tab,
        TabList,
    },

    props: {
        notificationRef: {
            type: Object,
            default: () => {},
        },

        showModal: {
            type: Boolean,
            default: () => {},
        },

        notification: {
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

    emits: ['delete', 'update:notification'],

    data() {
        return {
            originalNotification: null,
            mounted: false,
            tabsWithErrors: [],
        };
    },

    computed: {
        notificationErrors() {
            return this.notification.errors;
        },

        getFirstError() {
            if (!isEmpty(this.notificationErrors)) {
                return this.notificationErrors[Object.keys(this.notificationErrors)[0]][0] || '';
            }

            return null;
        },
    },

    created() {
        // Store this so we can cancel changes.
        this.originalNotification = this.clone(this.notification);

        // Add this to the global Vue instance so we can access it inside fields
        this.$store.dispatch('formie/setEditingNotification', this.notificationRef);
    },

    mounted() {
        // Set a small delay to show the modal, then try to render the form, which can take a little bit
        // for complex settings setups.
        setTimeout(() => {
            this.mounted = true;

            this.$nextTick().then(() => {
                // const $firstText = this.$refs.fieldForm.$el.parentNode.querySelector('input[type="text"]');

                // if ($firstText) {
                //     setTimeout(() => {
                //         $firstText.focus();
                //     }, 200)
                // }

                // Set any errors on the form, if they exist
                if (!isEmpty(this.fieldErrors)) {
                    this.$refs.fieldForm.setErrors(this.fieldErrors);

                    // Wait until FormKit has settled
                    setTimeout(() => {
                        this.updateTabs();
                    }, 50);
                }
            });
        }, 50);
    },

    destroy() {
        this.destroy();
    },

    methods: {
        destroy() {
            // Wait a little for the transition
            setTimeout(() => {
                this.$store.dispatch('formie/setEditingNotification', null);
            }, 200);
        },

        closeModal() {
            // Close the modal programatically, which will fire `@closed`
            this.$refs.modal.close();

            this.destroy();
        },

        deleteNotification() {
            this.$emit('delete');

            this.destroy();
        },

        tabErrorClass(tab) {
            return (this.tabsWithErrors.includes(tab)) ? 'error' : false;
        },

        submitHandler() {
            // Validation has already cleared the form

            // Add the notification (if a new one). Otherwise, model has already been updated in real-time
            this.notificationRef.addNotification();

            // Hide the modal
            this.closeModal();
        },

        submitHandlerRaw() {
            // When submitting from the form itself (hitting enter) we need to trigger any extra validation
            // functionality, like showing any errors on tabs.
            this.updateTabs();
        },

        onCancelModal() {
            // Restore original state and exit
            this.$emit('update:notification', this.originalNotification);

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
