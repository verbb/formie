<template>
    <tr class="fui-notification-row">
        <td class="flex flex-nowrap">
            <a href="#" class="flex flex-nowrap" style="--s: 0;" :class="{ 'error': hasError }" @click.prevent="openModal">
                <span class="status" :class="(notification.enabled ? 'on' : 'disabled')"></span>
                <strong><FormKit type="variableText" variables="plainTextVariables" :value="notification.name" readonly="true" /></strong>
            </a>

            <span v-if="isUnsaved" class="fui-unsaved-pill">{{ t('formie', 'Unsaved') }}</span>
        </td>

        <td>
            <span class="code">{{ notification.handle }}</span>
        </td>

        <td>
            <FormKit type="variableText" variables="plainTextVariables" :value="notification.subject" readonly="true" />
        </td>

        <td>
            <FormKit type="variableText" variables="emailVariables" :value="notification.to" readonly="true" />
        </td>

        <td>
            <a :title="t('formie', 'Duplicate')" role="button" href="#" class="fui-icon" @click.prevent="duplicateNotification">
                <!-- eslint-disable-next-line -->
                <svg aria-hidden="true" focusable="false" data-prefix="fas" data-icon="clone" class="svg-inline--fa fa-clone fa-w-16" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M464 0c26.51 0 48 21.49 48 48v288c0 26.51-21.49 48-48 48H176c-26.51 0-48-21.49-48-48V48c0-26.51 21.49-48 48-48h288M176 416c-44.112 0-80-35.888-80-80V128H48c-26.51 0-48 21.49-48 48v288c0 26.51 21.49 48 48 48h288c26.51 0 48-21.49 48-48v-48H176z" /></svg>
            </a>
        </td>

        <td>
            <a :title="t('formie', 'Delete')" role="button" href="#" class="delete icon" @click.prevent="deleteNotification"></a>
        </td>

        <notification-edit-modal
            v-if="showModal"
            v-model:showModal="showModal"
            :notification="notification"
            :notification-ref="this"
            :fields-schema="schema.fieldsSchema"
            :tabs-schema="schema.tabsSchema"
            @update:notification="notification = $event"
            @delete="deleteNotification"
            @closed="onModalClosed"
        />
    </tr>
</template>

<script>
import { isEmpty } from 'lodash-es';

import { generateHandle, getNextAvailableHandle, newId } from '@utils/string';

import NotificationEditModal from '@components/NotificationEditModal.vue';

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
            showModal: false,
        };
    },

    computed: {
        isNew() {
            return (this.notification.id || '').toString() === '';
        },

        hasError() {
            return !isEmpty(this.notification.errors);
        },

        isUnsaved() {
            if (this.$store.state.form.isStencil) {
                return false;
            }

            return this.isNew;
        },
    },

    mounted() {
        if (this.notification.id == 10) {
            // this.openModal();
        }
    },

    methods: {
        openModal() {
            this.showModal = true;
        },

        onModalClosed() {
            this.showModal = false;
        },

        addNotification() {
            if (this.notification.__isNew) {
                delete this.notification.__isNew;

                const payload = {
                    data: this.notification,
                };

                this.$store.dispatch('notifications/addNotification', payload);
            }
        },

        deleteNotification() {
            const { name } = this.notification;
            const confirmationMessage = Craft.t('formie', 'Are you sure you want to delete “{name}”?', { name });

            if (confirm(confirmationMessage)) {
                const payload = {
                    id: this.notification.__id,
                };

                this.$store.dispatch('notifications/deleteNotification', payload);
            }
        },

        duplicateNotification() {
            const newNotification = this.$store.getters['notifications/cloneNotification'](this.notification);

            this.$store.dispatch('notifications/addNotification', {
                data: newNotification,
            });
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
}

.fui-notification-row [data-type="variableText"] {
    overflow-x: auto;
}

.fui-notification-row .fui-tags-list .tiptap {
    white-space: nowrap;

    [contenteditable="false"] {
        white-space: nowrap !important;
    }
}

.fui-notification-row .fui-tag-wrap {
    white-space: normal !important;
}

</style>
