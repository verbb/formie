<template>
    <div>
        <button v-tooltip="button.text" class="btn fui-toolbar-btn" :class="{ active }" @click="onClick" @mousedown="onMouseDown">
            <i class="far" :class="'fa-' + button.icon"></i>
        </button>

        <div class="fui-toolbar-dropdown-container fui-toolbar-dropdown-link" style="display: none;">
            <div v-if="!active">
                <button
                    v-for="(option, i) in linkOptions" :key="i" class="fui-toolbar-dropdown" :class="'fui-toolbar-dropdown-item-link-' + option.refHandle" @click.prevent="openElementModal(option)"
                >
                    {{ option.optionTitle }}
                </button>

                <button class="fui-toolbar-dropdown fui-toolbar-dropdown-item-link" @click.prevent="openNewModal">
                    {{ 'Insert Link' | t('formie') }}
                </button>
            </div>

            <div v-else>
                <button class="fui-toolbar-dropdown fui-toolbar-dropdown-item-link" @click.prevent="openEditModal">
                    {{ 'Edit Link' | t('formie') }}
                </button>
            </div>

            <button class="fui-toolbar-dropdown fui-toolbar-dropdown-item-unlink" @click.prevent="unlinkAction">
                {{ 'Unlink' | t('formie') }}
            </button>
        </div>

        <link-toolbar-bubble :editor="editor" :field="field" />

        <link-toolbar-modal
            v-model="model"
            :show="showEditModal"
            :editor="editor"
            :field="field"
            confirm-button="Insert"
            modal-title="Insert Link"
            @close="closeModal"
        />
    </div>
</template>

<script>
import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';
import 'tippy.js/themes/light-border.css';

import LinkToolbarModal from './LinkToolbarModal.vue';
import LinkToolbarBubble from './LinkToolbarBubble.vue';

export default {
    name: 'LinkToolbarButton',

    components: {
        LinkToolbarModal,
        LinkToolbarBubble,
    },

    props: {
        button: {
            type: Object,
            default: () => {},
        },

        active: {
            type: Boolean,
            default: false,
        },

        field: {
            type: Object,
            default: () => {},
        },

        editor: {
            type: Object,
            default: () => {},
        },
    },

    data() {
        return {
            tippy: null,
            showEditModal: false,

            model: {},
        };
    },

    computed: {
        linkOptions() {
            return this.field.linkOptions;
        },
    },

    created() {
        this.resetModel();
    },

    mounted() {
        this.$nextTick(() => {
            const $template = this.$el.querySelector('.fui-toolbar-dropdown-link');
            const $button = this.$el;

            if ($template && $button) {
                $template.style.display = 'block';

                this.tippy = tippy($button, {
                    content: $template,
                    trigger: 'click',
                    allowHTML: true,
                    arrow: false,
                    interactive: true,
                    placement: 'bottom-start',
                    theme: 'light-border toolbar-dropdown',
                    zIndex: 1000,
                    hideOnClick: true,
                    offset: [0, 1],
                });
            }
        });
    },

    methods: {
        onMouseDown(e) {
            e.preventDefault();
        },

        onClick(e) {
            e.preventDefault();
        },

        resetModel() {
            this.model = {
                url: null,
                text: null,
                target: null,
            };
        },

        openNewModal() {
            this.tippy.hide();
            this.resetModel();

            // Check if we've selected a text node already and use that
            const selectedText = this.getSelectedText();

            if (selectedText) {
                this.model.text = selectedText;
            }

            this.showEditModal = true;
        },

        openEditModal() {
            this.tippy.hide();

            const { from, to } = this.editor.view.state.selection;
            const $node = this.editor.view.docView.domFromPos(from).node;
            const attrs = this.editor.getAttributes('link');

            this.model.text = $node.textContent;
            this.model.url = attrs.href;
            this.model.target = attrs.target;

            this.showEditModal = true;
        },

        closeModal() {
            this.showEditModal = false;
        },

        openElementModal(selectedElement) {
            this.tippy.hide();
            this.resetModel();

            Craft.createElementSelectorModal(selectedElement.elementType, {
                storageKey: 'FormieInput.LinkTo.' + selectedElement.elementType,
                sources: selectedElement.sources,
                criteria: selectedElement.criteria,
                defaultSiteId: this.elementSiteId,
                autoFocusSearchBox: false,
                onSelect: $.proxy((elements) => {
                    if (elements.length) {
                        const [element] = elements;

                        this.model.url = element.url + '#' + selectedElement.refHandle + ':' + element.id + '@' + element.siteId,
                        this.model.text = this.getSelectedText() || element.label;

                        this.tippy.hide();

                        this.showEditModal = true;
                    }
                }, this),
                closeOtherModals: false,
            });
        },

        getSelectedText() {
            const { from, to } = this.editor.state.selection;
            const selectedText = this.editor.state.doc.textBetween(from, to, ' ');

            if (selectedText) {
                return selectedText;
            }

            return false;
        },

        unlinkAction() {
            this.tippy.hide();

            this.editor.commands.link({});
        },
    },
};

</script>

<style lang="scss">

.fui-toolbar-dropdown {
    display: block;
    width: 100%;
    padding: 10px;
    margin: 0;
    text-align: left;
    text-decoration: none;
    white-space: nowrap;
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    outline: 0;

    &:hover {
        cursor: pointer;
        background: rgb(243 245 249);
    }
}

.tippy-box[data-theme~="toolbar-dropdown"] {
    min-width: 250px;

    .tippy-content {
        padding: 0;
    }
}

</style>
