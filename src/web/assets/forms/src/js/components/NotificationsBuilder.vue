<template>
    <div>
        <notification ref="newNotification" class="hidden" :schema="schema" :notification="newNotificationModel" />

        <div v-if="notifications.length">
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

            <a class="btn submit add icon new-btn " href="#" @click.prevent="newNotification">
                {{ 'New Notification' | t('formie') }}
            </a>
        </div>

        <div v-else>
            <div class="zilch">
                <p>{{ 'No notifications created.' | t('formie') }}</p>

                <a class="btn submit add icon new-btn " href="#" @click.prevent="newNotification">
                    {{ 'New Notification' | t('formie') }}
                </a>
            </div>
        </div>
    </div>
</template>

<script>
import Vue from 'vue';
import { mapState } from 'vuex';

import Notification from './Notification.vue';

export default {
    name: 'NotificationsBuilder',

    components: {
        Notification,
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
    },

};

</script>

<style>

.new-btn {
    font-size: 14px;
    line-height: 20px;
}

</style>
