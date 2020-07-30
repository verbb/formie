<template>
    <div>
        <existing-notification-modal ref="existingNotification" />

        <notification ref="newNotification" class="hidden" :schema="schema" :notification="newNotificationModel" />

        <div :class="{ 'zilch': !notifications.length }">
            <template v-if="notifications.length">
                <div class="tableview">
                    <div class="tablepane vue-admin-tablepane">
                        <table class="vuetable data fullwidth">
                            <thead>
                                <tr>
                                    <th>{{ 'Name' | t('formie') }}</th>
                                    <th>{{ 'Subject' | t('formie') }}</th>
                                    <th class="thin"></th>
                                </tr>
                            </thead>

                            <tbody class="vuetable-body">
                                <notification v-for="(notification) in notifications" :key="notification.id" ref="notification" :notification="notification" :schema="schema" />
                            </tbody>
                        </table>
                    </div>
                </div>

                <hr>
            </template>

            <template v-else>
                <p>{{ 'No notifications created.' | t('formie') }}</p>
            </template>

            <div class="fui-new-notification-buttons">
                <div class="btngroup submit first">
                    <a href="#" class="btn submit add icon" @click.prevent="newNotification">
                        {{ 'New Notification' | t('formie') }}
                    </a>

                    <div class="btn submit menubtn"></div>
                    <div class="menu">
                        <ul>
                            <li>
                                <a href="#" @click.prevent="existingNotification">
                                    {{ 'Select existing notification' | t('formie') }}
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Vue from 'vue';
import { mapState } from 'vuex';

import Notification from './Notification.vue';
import ExistingNotificationModal from './ExistingNotificationModal.vue';

export default {
    name: 'NotificationsBuilder',

    components: {
        Notification,
        ExistingNotificationModal,
    },

    props: {
        schema: {
            type: Object,
            default: () => {},
        },
    },

    data() {
        return {
            newNotificationModel: {},
        };
    },

    computed: {
        ...mapState(['notifications']),
    },

    methods: {
        newNotification() {
            // Create a new model each time we create a field. Populate any defaults
            this.newNotificationModel = {
                enabled: true,
                attachFiles: true,
                templateId: '',
            };

            this.$refs.newNotification.editNotification();
        },

        existingNotification() {
            this.$refs.existingNotification.showModal();
        },
    },

};

</script>

<style lang="scss">

.fui-new-notification-buttons {
    font-size: 14px;
    line-height: 20px;
}

.zilch .fui-new-notification-buttons .btngroup {
    justify-content: center;
}

</style>
