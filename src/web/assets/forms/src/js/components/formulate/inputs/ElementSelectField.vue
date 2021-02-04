<template>
    <div :id="id" class="elementselect">
        <div ref="elementSelectElements" class="elements"></div>

        <div class="flex">
            <button type="button" class="btn add icon dashed">{{ attributes.selectionLabel }}</button>
        </div>
    </div>
</template>

<script>
import FormulateInputMixin from '@braid/vue-formulate/src/FormulateInputMixin';

export default {
    name: 'ElementSelectField',

    mixins: [FormulateInputMixin],

    data() {
        return {
            id: 'element-' + Craft.randomString(10),
            modal: null,
            fetchedElementHtml: '',
        };
    },

    computed: {
        sources() {
            if (this.$editingField) {
                return this.$editingField.field.settings.sources;
            }

            return [];
        },

        source() {
            if (this.$editingField) {
                return this.$editingField.field.settings.source;
            }

            return [];
        },
    },

    watch: {
        sources(newValue) {
            // Create a new element select instance when changing sources
            this.createModal();
        },

        source(newValue) {
            // Create a new element select instance when changing sources
            this.createModal();
        },
    },

    created() {
        if (!this.context.model) {
            this.context.model = [];
        }
    },

    mounted() {
        this.$nextTick().then(() => {
            this.createModal();

            // Fetch the elements, if any
            if (this.context.model.length) {
                this.$axios.post(Craft.getActionUrl('formie/fields/render-elements'), { elements: this.context.model }).then((response) => {
                    if (response.data) {
                        // Set a flag to we know we've got out data
                        this.fetchedElementHtml = response.data;

                        // Have to directly modify the DOM for jQuery to pick this up...
                        this.$refs.elementSelectElements.innerHTML = response.data;

                        // Now trigger JS to kick in
                        this.createModal();
                    }
                }).catch(error => {
                    console.log(error);
                });
            }
        });
    },

    methods: {
        createModal() {
            var { config } = this.attributes;

            // If we have elements, wait until we fetch the HTML, then render
            if (this.context.model.length && !this.fetchedElementHtml) {
                return;
            }

            if (config) {
                config.id = this.id;
                config.storageKey = Craft.randomString(10);
                config.onSelectElements = this.onSelectElements;
                config.onRemoveElements = this.onRemoveElements;
                config.sources = this.sources;

                // Handle single-sources element select fields
                if (this.source && this.source.length) {
                    config.sources = [this.source];
                }

                // Check if the modal has been created already - only create it once
                if (this.modal) {
                    // Update the settings for existing modals
                    this.modal.setSettings(config, this.modal.settings);

                    // If the modal has already been opened, it won't get re-created, so force it to
                    // with the new settings
                    if (this.modal.modal) {
                        this.modal.modal.destroy();
                        delete this.modal.modal;
                    }
                } else {
                    this.modal = new Craft.BaseElementSelectInput(config);
                }
            }
        },

        onSelectElements(elements) {
            this.context.model = elements.map(element => {
                return { id: element.id, siteId: element.siteId };
            });
        },

        onRemoveElements(elements) {
            this.context.model = [];
        },
    },
};

</script>
