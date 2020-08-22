<script>
import get from 'lodash/get';
import isEmpty from 'lodash/isEmpty';

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
    },

    data() {
        return {
            loading: false,
            error: false,
            errorMessage: '',
            settings: {},
            sourceId: '',
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
    },

    methods: {
        get(object, key) {
            return get(object, key);
        },

        isEmpty(object) {
            return isEmpty(object);
        },

        getSourceFields(key) {
            var fields = [];

            if (this.sourceId) {
                var sources = get(this.settings, key, []);

                // Check for nested array - some integrations use optgroups
                if (Array.isArray(sources)) {
                    sources.forEach(item => {
                        if (item.id === this.sourceId) {
                            // eslint-disable-next-line
                            fields = item.fields;
                        }
                    });
                } else {
                    Object.keys(sources).forEach(key => {
                        sources[key].forEach(item => {
                            if (item.id === this.sourceId) {
                                // eslint-disable-next-line
                                fields = item.fields;
                            }
                        });
                    });
                }
            }

            return fields;
        },
                      
        refresh() {
            this.error = false;
            this.errorMessage = '';
            this.loading = true;

            const payload = {
                integration: this.handle,
            };

            this.$axios.post(Craft.getActionUrl('formie/integrations/form-settings'), payload).then((response) => {
                this.loading = false;

                if (response.data.error) {
                    this.error = true;

                    this.errorMessage = this.$options.filters.t('An error occurred.', 'formie');
                
                    if (response.data.error) {
                        this.errorMessage += '<br><code>' + response.data.error + '</code>';
                    }

                    return;
                }

                this.settings = response.data;
            }).catch(error => {
                this.loading = false;
                this.error = true;

                this.errorMessage = error;
                
                if (error.response.data.error) {
                    this.errorMessage += '<br><code>' + error.response.data.error + '</code>';
                }
            });
        },
    },

};

</script>
