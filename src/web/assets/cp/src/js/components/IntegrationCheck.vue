<template>
    <div>
        <div class="fui-integrations-check-wrap">
            <span class="status" :class="statusClass"></span>{{ statusText | t('formie') }}

            <button class="btn small" :class="{ 'fui-loading fui-loading-tiny': loading }" data-icon="refresh" title="$options.filters.t('Refresh', 'formie')" @click.prevent="refresh"></button>
        </div>

        <modal ref="modal" to="integrations-modals" :show-header="false" :show-footer="false" modal-class="fui-integration-error-modal">
            <template slot="body">
                <div class="fui-dialog-close" @click.prevent="hideModal"></div>

                <div class="fui-error-pane error">
                    <div class="fui-error-content">
                        <span data-icon="alert"></span>

                        <span class="error" v-html="errorMessage"></span>
                    </div>
                </div>
            </template>
        </modal>
    </div>
</template>

<script>
import Modal from '../../../../forms/src/js/components/Modal.vue';

export default {
    name: 'IntegrationCheck',

    components: {
        Modal,
    },

    props: {
        status: {
            type: String,
            required: true,
        },

        handle: {
            type: String,
            required: true,
        },
    },

    data() {
        return {
            statusText: '',
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
        this.statusText = this.status;
    },

    methods: {
        refresh() {
            this.error = false;
            this.errorMessage = '';
            this.loading = true;
            this.statusText = 'Connecting...';

            const payload = {
                integration: this.handle,
            };

            // Serialize the integration pane
            var $pane = document.getElementById('tab-' + this.handle);

            if ($pane) {
                var $inputElements = $pane.querySelectorAll('input, select, textarea');

                // Populate the payload with any real-time settings
                $inputElements.forEach($inputElement => {
                    var attribute = $inputElement.getAttribute('name');

                    payload[attribute] = $inputElement.value;
                });
            }

            this.$axios.post(Craft.getActionUrl('formie/integrations/check-connection'), payload).then((response) => {
                this.loading = false;

                if (response.data.error) {
                    this.error = true;
                    this.$refs.modal.showModal();

                    this.errorMessage = this.$options.filters.t('An error occurred.', 'formie');
                
                    if (response.data.error) {
                        this.errorMessage += '<br><br><code>' + response.data.error + '</code>';
                    }

                    this.statusText = 'Error';

                    return;
                }

                this.statusText = 'Connected';
            }).catch(error => {
                this.loading = false;
                this.error = true;
                this.$refs.modal.showModal();

                this.errorMessage = error;
                
                if (error.response.data.error) {
                    this.errorMessage += '<br><br><code>' + error.response.data.error + '</code>';
                }

                this.statusText = 'Error';
            });
        },

        hideModal() {
            this.$refs.modal.hideModal();
        },
    },

};

</script>

<style lang="scss">

#fui-integrations-settings {
    .modal-shade {
        position: absolute;
    }

    .modal {
        position: absolute;
        width: 500px;
        height: 350px;
        min-width: auto;
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
