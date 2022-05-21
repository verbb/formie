<template>
    <a href="#" @click.prevent="openModal">{{ t('formie', 'Select existing notification') }}</a>

    <modal ref="modal" v-model="showModal" :modal-class="['fui-edit-notification-modal', 'fui-existing-item-modal']" @click-outside="closeModal">
        <template v-slot:header>
            <h3 class="fui-modal-title">{{ t('formie', 'Add Existing Notification') }}</h3>

            <div class="fui-dialog-close" @click.prevent="closeModal"></div>
        </template>

        <template v-slot:body>
            <div v-if="existingNotifications.length" class="fui-modal-content-wrap">
                <div class="fui-modal-sidebar sidebar">
                    <nav v-if="filteredExistingNotifications.length">
                        <ul>
                            <li v-for="(item, index) in existingNotifications" :key="index" :class="{ 'heading': item.heading }">
                                <span v-if="item.heading">
                                    {{ item.heading }}
                                </span>

                                <a v-else :class="{ 'sel': selectedKey === item.key }" @click.prevent="selectTab(item.key)">
                                    <span class="label">{{ item.label }}</span>
                                </a>
                            </li>
                        </ul>
                    </nav>
                </div>

                <div class="fui-modal-content">
                    <div class="toolbar flex flex-nowrap">
                        <div class="flex-grow texticon search icon clearable">
                            <input v-model="search" class="text fullwidth" type="text" autocomplete="off" placeholder="Search">
                            <div class="clear hidden" title="Clear"></div>
                        </div>
                    </div>

                    <div v-if="filteredExistingNotifications.length">
                        <div v-for="(form, formIndex) in filteredExistingNotifications" :key="formIndex" :class="{ hidden: selectedKey !== form.key }">
                            <div class="fui-row small-padding">
                                <existing-notification
                                    v-for="(notification, notificationIndex) in form.notifications"
                                    :key="notificationIndex"
                                    :selected="isNotificationSelected(notification)"
                                    v-bind="notification"
                                    :notification="notification"
                                    @selected="notificationSelected"
                                />
                            </div>
                        </div>
                    </div>

                    <div v-else>
                        <p>{{ t('formie', 'No notifications found.') }}</p>
                    </div>
                </div>
            </div>

            <div v-else class="fui-modal-content-wrap">
                <div class="fui-modal-content">
                    <p>{{ t('formie', 'No existing notifications to select.') }}</p>
                </div>
            </div>
        </template>

        <template v-slot:footer>
            <div class="buttons left">
                <div class="spinner hidden"></div>
                <div class="btn" role="button" @click.prevent="closeModal">{{ t('app', 'Cancel') }}</div>
            </div>

            <div v-if="filteredExistingNotifications.length" class="buttons right">
                <input
                    type="submit"
                    :value="submitText"
                    :disabled="totalSelected === 0"
                    class="btn submit"
                    :class="{ 'disabled': totalSelected === 0 }"
                    @click.prevent="addNotifications"
                >
                        
                <div class="spinner hidden"></div>
            </div>
        </template>
    </modal>
</template>

<script>
import { mapState } from 'vuex';
import { findIndex } from 'lodash-es';
import { newId } from '@utils/string';

import Modal from '@components/Modal.vue';
import ExistingNotification from '@components/ExistingNotification.vue';

export default {
    name: 'ExistingNotificationModal',

    components: {
        Modal,
        ExistingNotification,
    },

    data() {
        return {
            showModal: false,
            search: '',
            selectedKey: '',
            selectedNotifications: [],
        };
    },

    computed: {
        ...mapState({
            existingNotifications: state => state.formie.existingNotifications,
            form: state => state.form,
        }),

        totalSelected() {
            return this.selectedNotifications.length;
        },

        filteredExistingNotifications() {
            return this.existingNotifications.reduce((acc, form) => {
                const notifications = form.notifications.filter(notification => {
                    const inLabel = notification.name.toLowerCase().includes(this.search.toLowerCase());

                    return inLabel;
                });

                return !notifications.length ? acc : acc.concat(Object.assign({}, form, { notifications }));
            }, []);
        },

        submitText() {
            if (this.totalSelected > 1) {
                return Craft.t('formie', 'Add {num} notifications', { num: this.totalSelected });
            } else if (this.totalSelected > 0) {
                return Craft.t('formie', 'Add {num} notification', { num: this.totalSelected });
            } else {
                return Craft.t('formie', 'Add notification');
            }
        },
    },

    created() {
        if (this.existingNotifications.length) {
            this.selectedKey = this.existingNotifications[0].key;
        }
    },

    methods: {
        openModal() {
            this.showModal = true;
        },

        closeModal() {
            this.selectedNotifications = [];

            this.showModal = false;
        },

        selectTab(key) {
            this.selectedKey = key;
        },

        isNotificationSelected(notification) {
            return findIndex(this.selectedNotifications, { id: notification.id }) > -1;
        },

        notificationSelected(notification, selected) {
            if (selected) {
                this.selectedNotifications.push(notification);
            } else {
                const index = findIndex(this.selectedNotifications, { id: notification.id });

                if (index > -1) {
                    this.selectedNotifications.splice(index, 1);
                }
            }
        },

        addNotifications() {
            for (const element of this.selectedNotifications) {
                const newNotification = this.clone(element.notification);
                newNotification['id'] = newId();
                
                delete newNotification['errors'];
                delete newNotification['hasError'];
                delete newNotification['uid'];

                this.$store.dispatch('notifications/addNotification', {
                    data: newNotification,
                });
            }

            this.closeModal();
        },
    },
};

</script>

<style lang="scss">

.fui-modal-footer .info {
    margin: 8px 10px 0 0;
}

</style>
