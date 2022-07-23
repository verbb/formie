<template>
    <slot
        v-if="mounted" v-bind="$data" :input="input" :get-source-fields="getSourceFields" :get="get" :is-empty="isEmpty"
        :refresh="refresh"
    ></slot>
</template>

<script>
import { get, set, isEmpty } from 'lodash-es';

export default {
    name: 'IntegrationFormSettings',

    props: {
        handle: {
            type: String,
            default: '',
        },

        source: {
            type: String,
            default: '',
        },

        formSettings: {
            type: [Object, Array],
            default: () => {},
        },

        values: {
            type: Object,
            default: () => {},
        },
    },

    data() {
        return {
            loading: false,
            error: false,
            errorMessage: '',
            success: false,
            mounted: false,
            settings: {},
            sourceId: '',
            model: {},
            globalParams: {},
        };
    },

    computed: {
        form() {
            return this.$store.state.form;
        },
    },

    created() {
        this.settings = this.formSettings;
        this.sourceId = this.source;

        // Create dynamic data variables based on whatever variables we pass in
        if (this.values) {
            Object.keys(this.values).forEach((prop) => {
                this.model[prop] = this.values[prop];
            });
        }
    },

    mounted() {
        // Prevent rendering slot components too early, as that can slow down the UI
        setTimeout(() => {
            this.mounted = true;

            // Re-bind any jQuery from Craft, which is slightly different due to scoped slots
            setTimeout(() => {
                Craft.initUiElements(this.$el.parentNode);
            }, 50);
        }, 50);
    },

    methods: {
        get(object, key) {
            return get(object, key);
        },

        isEmpty(object) {
            return isEmpty(object);
        },

        input(prop, value) {
            set(this, prop, value);
        },

        getSourceFields(key) {
            let fields = [];

            if (this.sourceId) {
                const sources = get(this.settings, key, []);

                // Check for nested array - some integrations use optgroups
                if (Array.isArray(sources)) {
                    sources.forEach((item) => {
                        if (item.id === this.sourceId) {
                            // eslint-disable-next-line
                            fields = item.fields;
                        }
                    });
                } else {
                    Object.keys(sources).forEach((key) => {
                        if (Array.isArray(sources[key])) {
                            sources[key].forEach((item) => {
                                if (item.id === this.sourceId) {
                                    // eslint-disable-next-line
                                    fields = item.fields;
                                }
                            });
                        }
                    });
                }
            }

            return fields;
        },

        refresh(payloadParams = {}) {
            this.success = false;
            this.error = false;
            this.errorMessage = '';
            this.loading = true;

            const data = {
                formId: this.form.id,
                integration: this.handle,
                ...this.globalParams,
                ...payloadParams,
            };

            Craft.sendActionRequest('POST', 'formie/integrations/form-settings', { data }).then((response) => {
                this.loading = false;

                if (response.data.error) {
                    this.error = true;

                    this.errorMessage = Craft.t('formie', 'An error occurred.');

                    if (response.data.error) {
                        this.errorMessage += `<br><code>${response.data.error}</code>`;
                    }

                    return;
                }

                this.settings = response.data;
                this.success = true;
            }).catch((error) => {
                this.loading = false;
                this.error = true;

                this.errorMessage = error;

                if (error.response.data.error) {
                    this.errorMessage += `<br><code>${error.response.data.error}</code>`;
                }
            });
        },
    },
};

</script>
