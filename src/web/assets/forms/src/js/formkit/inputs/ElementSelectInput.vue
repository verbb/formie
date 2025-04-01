<template>
    <div :id="id" class="elementselect">
        <ul ref="elements" class="elements chips chips-small" v-html="elementsHtml"></ul>

        <div class="flex">
            <button type="button" class="btn dashed add icon">{{ selectionLabel }}</button>
        </div>
    </div>
</template>

<script>
import { get } from 'lodash-es';
import { mapState } from 'vuex';

export default {
    props: {
        context: {
            type: Object,
            default: () => {},
        },
    },

    data() {
        return {
            id: `element-${Craft.randomString(10)}`,
            modal: null,
            elementsHtml: '',
        };
    },

    computed: {
        ...mapState({
            editingField: (state) => { return state.formie.editingField; },
            editingNotification: (state) => { return state.formie.editingNotification; },
        }),

        selectionLabel() {
            return get(this.context.attrs, 'selectionLabel');
        },

        sources() {
            if (this.editingField) {
                return this.editingField.field.settings.sources;
            }

            return get(this.context.attrs, 'sources');
        },

        source() {
            if (this.editingField) {
                return this.editingField.field.settings.source;
            }

            return [];
        },

        displayType() {
            if (this.editingField) {
                return this.editingField.field.settings.displayType;
            }

            return 'dropdown';
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
        if (!this.context._value) {
            this.context.node.input([]);
        }

        if (this.editingField) {
            this.elementsHtml = this.editingField.field[`${this.context.id}Html`];
        }

        if (this.editingNotification) {
            this.elementsHtml = this.editingNotification.notification.attachAssetsHtml;
        }
    },

    mounted() {
        this.$nextTick().then(() => {
            this.createModal();
        });
    },

    methods: {
        createModal() {
            const { config } = this.context.node;

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

                // Limit depending on display type - if not already set
                if (config.limit === undefined && this.displayType !== 'checkboxes') {
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
                    const jsClass = config.jsClass || 'Craft.BaseElementSelectInput';
                    const ClassReference = jsClass.split('.').reduce((acc, curr) => { return acc && acc[curr]; }, window);

                    this.modal = new ClassReference(config);
                }
            }
        },

        onSelectElements(elements) {
            this.domToModel();
        },

        onRemoveElements() {
            this.domToModel();
        },

        setElementsHtml($html) {
            if (this.editingField) {
                this.editingField.field[`${this.context.id}Html`] = $html;
            }

            if (this.editingNotification) {
                this.editingNotification.notification.attachAssetsHtml = $html;
            }
        },

        domToModel() {
            const elements = [];

            this.modal.$elements.each((index, $element) => {
                elements.push({ id: $element.dataset.id, siteId: $element.dataset.siteId });
            });

            this.context.node.input(elements);

            // Store the HTML of the element select, globally. This is because next time we open a modal
            // the component will have its HTML reset, where it actually has a value. Super-gross.
            // Provide a slight delay to ensure the DOM is ready
            setTimeout(() => {
                const $elements = $(this.$refs.elements).clone();

                // There's a `visibility: hidden` added to elements that we want to remove
                $elements.find('.element').removeAttr('style');

                this.setElementsHtml($elements.html());
            }, 200);
        },
    },
};

</script>

<style lang="scss">

.field.field-wrapper .elementselect li {
    list-style: none;
}

</style>
