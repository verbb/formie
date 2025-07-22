<template>
    <slot
        v-if="mounted"
        v-bind="$data"
        :input="input"
        :get-source-fields="getSourceFields"
        :get-collection-fields="getCollectionFields"
        :get="get"
        :is-empty="isEmpty"
        :refresh="refresh"
    ></slot>
</template>

<script>
import { get, set, isEmpty } from 'lodash-es';

import { toBoolean } from '@utils/bool';
import { getErrorMessage } from '@utils/forms';

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

        mappingFields() {
            return this.$store.state.integrationFieldSelectOptions;
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

        getCollectionFields(sourceId, collection) {
            return get(collection, sourceId, []);
        },

        refresh(payloadParams = {}) {
            this.success = false;
            this.error = false;
            this.errorMessage = '';
            this.loading = true;

            const data = {
                formId: this.form.id,
                integration: this.handle,
                settings: {},
                ...this.globalParams,
                ...payloadParams,
            };

            // Get the current values for this integrations settings to send that
            const postData = Garnish.getPostData(this.$el.parentNode);
            const postFields = Craft.expandPostArray(postData);
            const { integrations } = postFields.settings;
            const integrationKey = Object.keys(integrations)[0];

            data.settings = integrations[integrationKey];

            Craft.sendActionRequest('POST', 'formie/integrations/form-settings', { data }).then((response) => {
                this.loading = false;

                if (response.data.error) {
                    this.error = true;

                    const info = getErrorMessage(response.data.error);
                    this.errorMessage = `<strong>${info.heading}</strong><br><small>${info.text}<br>${info.trace}</small>`;

                    return;
                }

                this.settings = response.data;
                this.success = true;
            }).catch((error) => {
                this.loading = false;
                this.error = true;

                const info = getErrorMessage(error);
                this.errorMessage = `<strong>${info.heading}</strong><br><small>${info.text}<br>${info.trace}</small>`;
            });
        },
    },
};

</script>
