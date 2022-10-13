<template>
    <div class="fui-notification-preview">
        <div class="field field-wrapper flex">
            <div class="heading">
                <label class="">{{ t('formie', 'Email Preview') }}</label>

                <div class="instructions">
                    <p>{{ t('formie', 'The example below shows a preview of this email notification.') }}</p>
                </div>
            </div>

            <div class="">
                <a href="#" class="btn submit fui-refresh-btn" :class="{ 'fui-loading fui-loading-sm': loading }" @click.prevent="updatePreview">{{ t('formie', 'Refresh') }}</a>
            </div>
        </div>

        <div class="fui-email-preview">
            <div class="fui-email-header"></div>

            <div class="fui-email-meta">
                <div class="fui-email-meta-label">{{ t('formie', 'To:') }}</div>
                <div class="fui-email-meta-value">{{ emailAddress(email.to) }}</div>
            </div>

            <div v-if="email.cc" class="fui-email-meta">
                <div class="fui-email-meta-label">{{ t('formie', 'Cc:') }}</div>
                <div class="fui-email-meta-value">{{ emailAddress(email.cc) }}</div>
            </div>

            <div v-if="email.bcc" class="fui-email-meta">
                <div class="fui-email-meta-label">{{ t('formie', 'Bcc:') }}</div>
                <div class="fui-email-meta-value">{{ emailAddress(email.bcc) }}</div>
            </div>

            <div class="fui-email-meta">
                <div class="fui-email-meta-label">{{ t('formie', 'Subject:') }}</div>
                <div class="fui-email-meta-value">{{ email.subject }}</div>
            </div>

            <div v-if="email.replyTo" class="fui-email-meta">
                <div class="fui-email-meta-label">{{ t('formie', 'Reply To:') }}</div>
                <div class="fui-email-meta-value">{{ emailAddress(email.replyTo) }}</div>
            </div>

            <div class="fui-email-meta">
                <div class="fui-email-meta-label">{{ t('formie', 'From:') }}</div>
                <div class="fui-email-meta-value">{{ emailAddress(email.from) }}</div>
            </div>

            <div class="fui-email-body">
                <!-- Note the odd use of length - we'll get an empty `<p>` back for empty -->
                <iframe
                    v-if="email.body && email.body.length > 10"
                    id="email-iframe"
                    src="about:blank"
                    frameborder="0"
                    style="height: 100vh; width: 100%;"
                ></iframe>

                <div v-else class="warning with-icon">{{ t('formie', 'No email content.') }}</div>
            </div>

            <div class="fui-email-footer"></div>

            <div v-if="loading" class="fui-loading-pane">
                <div class="fui-loading fui-loading-lg"></div>
            </div>

            <div v-if="error" class="fui-error-pane error">
                <div class="fui-error-content">
                    <span data-icon="alert"></span>

                    <span class="error" v-html="errorMessage"></span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { mapState } from 'vuex';

export default {
    name: 'NotificationPreview',

    data() {
        return {
            email: {},
            error: false,
            errorMessage: '',
            loading: false,
        };
    },

    computed: {
        ...mapState({
            form: (state) => { return state.form; },
        }),

        notification() {
            // Not amazing, but provide/inject won't work
            return this.$parent.$parent.$parent.$parent.$parent.node._value;
        },

        isStencil() {
            return this.$store.state.form.isStencil;
        },
    },

    created() {
        this.updatePreview();
    },

    methods: {
        updateiFrame() {
            this.$nextTick().then(() => {
                const $iframe = this.$el.querySelector('#email-iframe');

                if ($iframe && this.email.body) {
                    const doc = $iframe.contentWindow.document;

                    if (doc) {
                        doc.open();
                        doc.write(`<html><head><title></title></head><body>${this.email.body}</body></html>`);
                        doc.close();
                    }
                }
            });
        },

        updatePreview() {
            this.error = false;
            this.errorMessage = '';
            this.loading = true;

            const data = {
                handle: this.form.handle,
                notification: this.notification,
            };

            // Only add the Form ID if not a stencil
            if (!this.isStencil) {
                data.formId = this.form.id;
            }

            Craft.sendActionRequest('POST', 'formie/email/preview', { data }).then((response) => {
                this.loading = false;

                if (response.data.error) {
                    this.error = true;

                    this.errorMessage = Craft.t('formie', 'An error occurred.');

                    if (response.data.error) {
                        this.errorMessage += `<br><br><code>${response.data.error}</code>`;
                    }

                    return;
                }

                this.email = response.data;

                this.updateiFrame();
            }).catch((error) => {
                this.loading = false;
                this.error = true;

                this.errorMessage = error;

                if (error.response.data.error) {
                    this.errorMessage += `<br><br><code>${error.response.data.error}</code>`;
                }
            });
        },

        emailAddress(object) {
            if (!object) {
                return '';
            }

            const [email] = Object.keys(object);

            if (object[email]) {
                return `${object[email]} <${email}>`;
            }

            return email;
        },
    },
};

</script>

<style lang="scss">

.fui-email-preview {
    position: relative;
    box-shadow: 0 0 0 1px rgba(49,49,93,.05), 0 2px 5px 0 rgba(49,49,93,.075), 0 1px 3px 0 rgba(49,49,93,.15);
    background-color: #fff;
    border-radius: 4px;
}

.fui-email-header,
.fui-email-footer {
    background: #f5f8fc;
    width: 100%;
    height: 20px;
}

.fui-email-header {
    border-bottom: 1px #e5e5e5 solid;
    border-radius: 4px 4px 0 0;
}

.fui-email-footer {
    border-top: 1px #e5e5e5 solid;
    border-radius: 0 0 4px 4px;
}

.fui-email-meta {
    display: flex;
    font-size: 13px;
    padding: 4px 0;
    border-bottom: 1px #e5e5e5 solid;
}

.fui-email-meta-label {
    width: 75px;
    text-align: right;
    font-weight: 700;
    color: #b2bac1;
    margin-right: 8px;
    flex: 0 0 auto;
}

.fui-email-body {
    min-height: 20rem;
    padding: 16px;
}

.fui-email-preview .fui-error-pane,
.fui-email-preview .fui-loading-pane {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    background: #fff;
    background: rgba(255, 255, 255, 0.7);
}

.fui-email-preview .fui-loading {
    position: absolute;
    top: 50%;
    left: 50%;
    background: #fff;
}

.fui-notification-preview .fui-refresh-btn {
    padding: 5px 10px;
    font-size: 12px;
    font-weight: 600;
    height: auto;
}

.fui-notification-preview .field {
    justify-content: space-between;
}

.fui-email-preview .fui-error-pane {
    align-items: center;
    justify-content: center;
    display: flex;

    [data-icon] {
        display: block;
        font-size: 3em;
        margin-bottom: 0.5rem;
    }
}

.fui-email-preview .fui-error-content {
    text-align: center;
    max-width: 35rem;
}

</style>
