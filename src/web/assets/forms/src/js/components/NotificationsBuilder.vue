<template>
    <div>
        <notification ref="newNotification" class="hidden" :schema="schema" :notification="newNotificationModel" />

        <div :class="{ 'zilch': !notifications.length }">
            <template v-if="notifications.length">
                <div class="tableview">
                    <div class="tablepane vue-admin-tablepane">
                        <table class="vuetable data fullwidth">
                            <thead>
                                <tr>
                                    <th>{{ t('formie', 'Name') }}</th>
                                    <th>{{ t('formie', 'Subject') }}</th>
                                    <th class="thin"></th>
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
                <p>{{ t('formie', 'No notifications created.') }}</p>
            </template>

            <div class="fui-new-notification-buttons">
                <div class="btngroup submit first">
                    <a href="#" class="btn submit add icon" @click.prevent="newNotification">
                        {{ t('formie', 'New Notification') }}
                    </a>

                    <div class="btn submit menubtn"></div>
                    <div class="menu">
                        <ul>
                            <li>
                                <existing-notification-modal />
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { mapState } from 'vuex';

import Notification from '@components/Notification.vue';
import ExistingNotificationModal from '@components/ExistingNotificationModal.vue';

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
        ...mapState(['notifications', 'form']),
    },

    methods: {
        newNotification() {
            // Create a new model each time we create a field. Populate any defaults
            this.newNotificationModel = {
                enabled: true,
                attachFiles: true,
                enableConditions: false,
                templateId: this.form.settings.defaultEmailTemplateId,
            };

            this.$refs.newNotification.openModal();
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
