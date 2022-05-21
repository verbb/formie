<template>
    <div>
        <button v-tooltip="{ content: title, theme: 'fui-editor-tooltip' }" class="btn fui-toolbar-btn" :class="{ 'active': active }" @click.prevent="() => {}" @mousedown="onMouseDown">
            <svg-icon :content="{ icon, svg }" />
        </button>

        <div class="fui-toolbar-dropdown-container fui-toolbar-dropdown-link" style="display: none;">
            <div v-if="!active">
                <button
                    v-for="(option, i) in linkOptions" :key="i" class="fui-toolbar-dropdown" :class="'fui-toolbar-dropdown-item-link-' + option.refHandle" @click.prevent="openElementModal(option)"
                >
                    {{ option.optionTitle }}
                </button>

                <button class="fui-toolbar-dropdown fui-toolbar-dropdown-item-link" @click.prevent="openNewModal">
                    {{ t('formie', 'Insert Link') }}
                </button>
            </div>

            <div v-else>
                <button class="fui-toolbar-dropdown fui-toolbar-dropdown-item-link" @click.prevent="openEditModal">
                    {{ t('formie', 'Edit Link') }}
                </button>
            </div>

            <button class="fui-toolbar-dropdown fui-toolbar-dropdown-item-unlink" @click.prevent="unlinkAction">
                {{ t('formie', 'Unlink') }}
            </button>
        </div>

        <link-menu-bubble :editor="editor" :field="field" />

        <link-menu-modal
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
import 'tippy.js/themes/light-border.css';

import SvgIcon from '../SvgIcon.vue';
import LinkMenuBubble from './LinkMenuBubble.vue';
import LinkMenuModal from './LinkMenuModal.vue';

export default {
    name: 'MenuBarItem',

    components: {
        SvgIcon,
        LinkMenuModal,
        LinkMenuBubble,
    },

    props: {
        field: {
            type: Object,
            default: null,
        },

        editor: {
            type: Object,
            default: null,
        },

        icon: {
            type: String,
            default: null,
        },

        svg: {
            type: String,
            default: null,
        },

        title: {
            type: String,
            default: null,
        },

        isActive: {
            type: Function,
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
        active() {
            return this.isActive && this.isActive();
        },

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
                storageKey: `FormieInput.LinkTo.${selectedElement.elementType}`,
                sources: selectedElement.sources,
                criteria: selectedElement.criteria,
                defaultSiteId: this.elementSiteId,
                autoFocusSearchBox: false,
                onSelect: $.proxy((elements) => {
                    if (elements.length) {
                        const [element] = elements;

                        this.model.url = `${element.url}#${selectedElement.refHandle}:${element.id}@${element.siteId}`,
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

            this.editor.chain().focus().unsetLink().run();
        },

        onMouseDown(e) {
            e.preventDefault();
        },
    },
};

</script>
