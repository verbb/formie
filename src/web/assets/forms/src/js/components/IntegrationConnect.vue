<template>
    <div class="field lightswitch-field">
        <div class="heading">
            <span class="status" :class="statusClass"></span>
            <span>{{ t('formie', statusText) }}</span>
        </div>

        <div class="input ltr">
            <button class="btn small" :class="{ 'fui-loading fui-loading-tiny': loading }" :title="t('formie', 'Refresh')" @click.prevent="refresh">{{ t('formie', 'Refresh') }}</button>

            <modal ref="modal" v-model="showModal" :show-header="false" :show-footer="false" modal-class="fui-integration-error-modal" @click-outside="closeModal">
                <template #body>
                    <div class="fui-dialog-close" @click.prevent="closeModal"></div>

                    <div class="fui-error-pane error">
                        <div class="fui-error-content">
                            <span data-icon="alert"></span>

                            <span class="error" v-html="errorMessage"></span>
                        </div>
                    </div>
                </template>
            </modal>
        </div>
    </div>
</template>

<script>
import Modal from '@components/Modal.vue';

export default {
    name: 'IntegrationConnect',

    components: {
        Modal,
    },

    props: {
        connected: {
            type: Boolean,
        },
    },

    data() {
        return {
            statusText: '',
            showModal: false,
            error: false,
            errorMessage: '',
            loading: false,
        };
    },

    computed: {
        statusClass() {
            if (this.statusText === 'Error') {
                return 'off';
            }

            if (this.statusText === 'Connected') {
                return 'on';
            }

            return '';
        },
    },

    created() {
        this.statusText = this.connected ? 'Connected' : 'Not connected';
    },

    methods: {
        getFormInputs() {
            let inputs = [];

            // Serialize the integration pane
            let $form = document.getElementById('main-form');

            // Check for when `allowAdminChanges = false` on production - no form
            if (!$form) {
                $form = document.getElementById('main');
            }

            if ($form) {
                inputs = $form.querySelectorAll('input, select, textarea');
            }

            return inputs;
        },

        serializeForm() {
            const values = {};

            this.getFormInputs().forEach(($inputElement) => {
                const attribute = $inputElement.getAttribute('name');

                values[attribute] = $inputElement.value;
            });

            return values;
        },

        refresh() {
            this.showModal = false;
            this.error = false;
            this.errorMessage = '';
            this.loading = true;
            this.statusText = 'Connecting...';

            const data = this.serializeForm();

            Craft.sendActionRequest('POST', 'formie/integrations/check-connection', { data }).then((response) => {
                this.loading = false;

                if (response.data.message) {
                    this.error = true;
                    this.showModal = true;

                    this.errorMessage = Craft.t('formie', 'An error occurred.');
                    this.errorMessage += `<br><br><code>${response.data.message}</code>`;

                    this.statusText = 'Error';

                    return;
                }

                this.statusText = 'Connected';
            }).catch((error) => {
                this.loading = false;
                this.error = true;
                this.showModal = true;

                this.errorMessage = error;

                if (error.response.data.message) {
                    this.errorMessage += `<br><br><code>${error.response.data.message}</code>`;
                }

                this.statusText = 'Error';
            });
        },

        closeModal() {
            this.showModal = false;
        },
    },

};

</script>

<style lang="scss">

.fui-integrations-settings {
    .modal {
        position: absolute;
        width: 45%;
        height: 350px;
        min-width: 600px;
        min-height: auto;
        box-shadow: 0 0 20px rgba(63, 77, 90, 0.1);
        border: 1px solid #cdd8e4;
        border-radius: 10px;
    }
}

.fui-integration-error-modal .fui-dialog-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    z-index: 3;
}

.fui-integration-error-modal .fui-error-pane {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    z-index: 1;
}

.fui-integration-error-modal .fui-error-pane {
    align-items: center;
    justify-content: center;
    display: flex;

    [data-icon] {
        display: block;
        font-size: 3em;
        margin-bottom: 0.5rem;
    }
}

.fui-integration-error-modal .fui-error-content {
    text-align: center;
    width: 90%;
}

</style>
