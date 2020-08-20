<template>
    <tr class="fui-notification-row">
        <td class="">
            <a href="#" :class="{ 'error': false }" @click.prevent="editNotification">
                <span class="status" :class="{ 'on': !!+notification.enabled }"></span>
                <strong>{{ notification.name }}</strong>
            </a>
            
            <span v-if="isUnsaved" class="fui-unsaved-pill">{{ 'Unsaved' | t('formie') }}</span>
        </td>

        <td class="">
            <span>{{ notification.subject }}</span>
        </td>

        <td>
            <a :title="$options.filters.t('Duplicate', 'formie')" role="button" href="#" class="fui-icon" @click.prevent="duplicateNotification">
                <svg
                    aria-hidden="true" focusable="false" data-prefix="fas" data-icon="clone" class="svg-inline--fa fa-clone fa-w-16"
                    role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"
                ><path fill="currentColor" d="M464 0c26.51 0 48 21.49 48 48v288c0 26.51-21.49 48-48 48H176c-26.51 0-48-21.49-48-48V48c0-26.51 21.49-48 48-48h288M176 416c-44.112 0-80-35.888-80-80V128H48c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48h288c26.51 0 48-21.49 48-48v-48H176z" /></svg>
            </a>
        </td>

        <td>
            <a :title="$options.filters.t('Delete', 'formie')" role="button" href="#" class="delete icon" @click.prevent="deleteNotification"></a>
        </td>

        <notification-edit-modal
            v-if="modalActive"
            ref="editNotificationModal"
            :visible="modalVisible"
            :notification="notification"
            :fields-schema="schema.fieldsSchema"
            :tabs-schema="schema.tabsSchema"
            @close="onModalClose"
            @delete="deleteNotification"
        />
    </tr>
</template>

<script>
import { newId } from '../utils/string';

import NotificationEditModal from './NotificationEditModal.vue';

export default {
    name: 'Notification',

    components: {
        NotificationEditModal,
    },

    props: {
        notification: {
            type: Object,
            default: () => {},
        },

        schema: {
            type: Object,
            default: () => {},
        },
    },

    data() {
        return {
            modalActive: false,
            modalVisible: false,
        };
    },

    computed: {
        isNew() {
            return (this.notification.id || '').toString() === '';
        },

        isUnsaved() {
            return (this.notification.id || '').toString().startsWith('new');
        },
    },

    mounted() {
        // noinspection EqualityComparisonWithCoercionJS
        if (this.notification.id == 1) {
            // this.editNotification();
        }
    },

    methods: {
        editNotification() {
            this.modalActive = true;
            this.modalVisible = true;
        },

        addNotification() {
            if (this.isNew) {
                const payload = {
                    data: Object.assign(this.notification, { id: newId() }),
                };

                this.$store.dispatch('notifications/addNotification', payload);
            }

            // Update the form state to trigger content-change warnings
            this.$set(this.$store.state.form.pages[0], 'notificationFlag', true);
        },

        deleteNotification() {
            const { name } = this.notification;
            const confirmationMessage = Craft.t('formie', 'Are you sure you want to delete “{name}”?', { name });

            if (confirm(confirmationMessage)) {
                const payload = {
                    id: this.notification.id,
                };

                this.$store.dispatch('notifications/deleteNotification', payload);

                // Update the form state to trigger content-change warnings
                this.$set(this.$store.state.form.pages[0], 'notificationFlag', true);
            }
        },

        duplicateNotification() {
            const newNotification = clone(this.notification);
            newNotification['id'] = newId();
            
            delete newNotification['errors'];
            delete newNotification['hasError'];
            delete newNotification['uid'];

            this.$store.dispatch('notifications/addNotification', {
                data: newNotification,
            });

            // Update the form state to trigger content-change warnings
            this.$set(this.$store.state.form.pages[0], 'notificationFlag', true);
        },

        onModalClose() {
            this.modalActive = false;
            this.modalVisible = false;
        },
    },

};

</script>

<style lang="scss">

.fui-notification-row .fui-icon {
    display: block;
    width: 12px;
    height: 12px;
    color: #cbcfd4;
    margin-top: 2px;

    &:hover {
        color: #0B69A3;
    }

    svg {
        display: block;
    }
}

.fui-unsaved-pill {
    position: relative;
    background-color: #e5edf6;
    color: #92a3b7;
    border-radius: 2px;
    display: inline-flex;
    padding: 0 5px;
    margin: 0;
    font-size: 12px;
    white-space: nowrap;
    text-transform: uppercase;
    font-weight: 700;
    border: 1px #c8d3e0 solid;
    margin-left: 10px;
}

</style>
