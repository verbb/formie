<template>
    <modal ref="modal" to="notification-modals" :modal-class="['fui-edit-notification-modal', 'fui-existing-item-modal']">
        <template slot="header">
            <h3 class="fui-modal-title">{{ 'Add Existing Notification' | t('formie') }}</h3>

            <div class="fui-dialog-close" @click.prevent="hideModal"></div>
        </template>

        <template slot="body">
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
                        <p>{{ 'No notifications found.' | t('formie') }}</p>
                    </div>
                </div>
            </div>

            <div v-else class="fui-modal-content-wrap">
                <div class="fui-modal-content">
                    <p>{{ 'No existing notifications to select.' | t('formie') }}</p>
                </div>
            </div>
        </template>

        <template slot="footer">
            <div class="buttons left">
                <div class="spinner hidden"></div>
                <div class="btn" role="button" @click.prevent="hideModal">{{ 'Cancel' | t('app') }}</div>
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
import findIndex from 'lodash/findIndex';
import { newId } from '../utils/string';

import Modal from './Modal.vue';
import ExistingNotification from './ExistingNotification.vue';

export default {
    name: 'ExistingNotificationModal',

    components: {
        Modal,
        ExistingNotification,
    },

    data() {
        return {
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
                return this.$options.filters.t('Add {num} notifications', 'formie', { num: this.totalSelected });
            } else if (this.totalSelected > 0) {
                return this.$options.filters.t('Add {num} notification', 'formie', { num: this.totalSelected });
            } else {
                return this.$options.filters.t('Add notification', 'formie');
            }
        },
    },

    created() {
        if (this.existingNotifications.length) {
            this.selectedKey = this.existingNotifications[0].key;
        }
    },

    methods: {
        showModal() {
            this.$refs.modal.showModal();
        },

        hideModal() {
            this.selectedNotifications = [];
            this.$refs.modal.hideModal();
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
                const newNotification = clone(element.notification);
                newNotification['id'] = newId();
                
                delete newNotification['errors'];
                delete newNotification['hasError'];
                delete newNotification['uid'];

                this.$store.dispatch('notifications/addNotification', {
                    data: newNotification,
                });
            }

            this.hideModal();
        },
    },
};

</script>

<style lang="scss">

.fui-modal-footer .info {
    margin: 8px 10px 0 0;
}

</style>
