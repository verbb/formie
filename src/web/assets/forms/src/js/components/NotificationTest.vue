<template>
    <div class="fui-notification-test">
        <div class="field field-wrapper">
            <div class="heading">
                <label class="">{{ 'Send Test Email' | t('formie') }}</label>

                <div class="instructions">
                    <p>{{ 'Use the form below to send a test email to the nominated email address.' | t('formie') }}</p>
                </div>
            </div>

            <div class="fui-test-input">
                <input v-model="to" class="text fullwidth" type="text">

                <a href="#" class="btn submit fui-refresh-btn" :class="{ 'fui-loading fui-loading-sm': loading }" @click.prevent="sendTestEmail">{{ 'Send Test Email' | t('formie') }}</a>
            </div>
        </div>

        <div v-if="error || success" class="fui-message-pane" :class="{ 'error': error, 'success': success }">
            <div class="fui-message-content">
                <span v-if="error" data-icon="alert"></span>

                <span v-html="message"></span>
            </div>
        </div>
    </div>
</template>

<script>
import { mapState } from 'vuex';

export default {
    name: 'NotificationTest',

    data() {
        return {
            to: null,
            error: false,
            success: false,
            loading: false,
            message: '',
        };
    },

    computed: {
        ...mapState({
            form: state => state.form,
        }),

        notification() {
            // Not amazing, but provide/inject won't work
            return this.$parent.$parent.$parent.$attrs.notification;
        },
    },

    created() {
        // Populate the current email
        if (this.$attrs['user-email']) {
            this.to = this.$attrs['user-email'];
        }
    },

    methods: {
        sendTestEmail() {
            this.error = false;
            this.success = false;
            this.loading = true;
            this.message = '';

            const payload = {
                formId: this.form.id,
                notification: this.notification,
                to: this.to,
            };

            this.$axios.post(Craft.getActionUrl('formie/email/send-test-email'), payload).then((response) => {
                this.loading = false;

                if (response.data.success) {
                    this.success = true;

                    this.message = this.$options.filters.t('Email sent successfully. Please check your email.', 'formie');
                }

                if (response.data.error) {
                    this.error = true;

                    this.message = this.$options.filters.t('Error sending test email.', 'formie');
                
                    if (response.data.error) {
                        this.message += '<br><br><code>' + response.data.error + '</code>';
                    }

                    return;
                }
            }).catch(error => {
                this.loading = false;
                this.error = true;

                this.message = error;
                
                if (error.response.data.error) {
                    this.message += '<br><br><code>' + error.response.data.error + '</code>';
                }
            });
        },
    },
};

</script>

<style lang="scss">

.fui-test-input {
    display: flex;

    .text {
        margin-right: 1rem;
    }
}

.fui-notification-test .fui-message-pane {
    margin-top: 10px;
}

</style>
