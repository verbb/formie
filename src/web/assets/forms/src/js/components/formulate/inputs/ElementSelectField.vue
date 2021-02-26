<template>
    <div :id="id" class="elementselect">
        <div class="elements" v-html="defaultValueHtml"></div>

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

        displayType() {
            if (this.$editingField) {
                return this.$editingField.field.settings.displayType;
            }

            return 'dropdown';
        },

        defaultValueHtml() {
            if (this.$editingField) {
                return this.$editingField.field.defaultValueHtml;
            }

            return '';
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

        displayType(newValue) {
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
        this.createModal();
    },

    methods: {
        createModal() {
            var { config } = this.attributes;

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

                // Limit depending on display type
                if (this.displayType !== 'checkboxes') {
                    config.limit = 1;
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
            this.domToModel();
        },

        onRemoveElements() {
            this.domToModel();
        },

        domToModel() {
            var elements = [];

            this.modal.$elements.each((index, $element) => {
                elements.push({ id: $element.dataset.id, siteId: $element.dataset.siteId });
            });

            this.context.model = elements;
        },
    },
};

</script>
