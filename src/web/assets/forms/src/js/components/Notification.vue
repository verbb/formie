<template>
    <tr>
        <td class="">
            <a href="#" :class="{ 'error': false }" @click.prevent="editNotification">
                <span class="status" :class="{ 'on': !!+notification.enabled }"></span>
                <strong>{{ notification.name }}</strong>
            </a>
        </td>

        <td class="">
            <span>{{ notification.subject }}</span>
        </td>

        <td>
            <a title="Delete" role="button" href="#" class="delete icon" @click.prevent="deleteNotification"></a>
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
        },

        deleteNotification() {
            const { name } = this.notification;
            const confirmationMessage = Craft.t('formie', 'Are you sure you want to delete “{name}”?', { name });

            if (confirm(confirmationMessage)) {
                const payload = {
                    id: this.notification.id,
                };

                this.$store.dispatch('notifications/deleteNotification', payload);
            }
        },

        onModalClose() {
            this.modalActive = false;
            this.modalVisible = false;
        },
    },

};

</script>
