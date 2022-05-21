<template>
    <div class="fui-link-menu-bubble" style="display: none;">
        <a :href="model.url" target="_blank" v-html="truncate(model.url, 30)"></a>
        <a href="#" @click.prevent="edit">{{ t('formie', 'Edit') }}</a>
        <a href="#" @click.prevent="unlink">{{ t('formie', 'Unlink') }}</a>

        <link-menu-modal
            v-model="model"
            :show="showEditModal"
            :editor="editor"
            :field="field"
            confirm-button="Update"
            modal-title="Edit Link"
            @close="closeModal"
        />
    </div>
</template>

<script>
import LinkMenuModal from './LinkMenuModal.vue';
import { getMarkRange } from '@utils/tiptap/marks';

import tippy, { followCursor } from 'tippy.js';
import 'tippy.js/dist/tippy.css';

export default {
    name: 'LinkMenuBubble',

    components: {
        LinkMenuModal,
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
    },

    data() {
        return {
            tippy: null,
            showEditModal: false,

            model: {
                url: null,
                text: null,
                target: null,
            },
        };
    },

    watch: {
        editor: {
            immediate: true,
            handler(editor) {
                if (editor) {
                    this.$nextTick(() => {
                        // Attach a custom event to the editor whenever a link node is clicked
                        editor.on('fui:link-clicked', this.renderBubble);
                    });
                }
            },
        },
    },

    methods: {
        renderBubble() {
            const { doc, selection, schema } = this.editor.view.state;

            const range = getMarkRange(doc.resolve(selection.anchor), schema.marks.link);

            if (range) {
                const $node = this.editor.view.docView.domFromPos(range.from).node;

                if ($node) {
                    this.$el.style.display = 'block';

                    // Update our model
                    const attrs = this.editor.getAttributes('link');
                    this.model.text = $node.textContent;
                    this.model.url = attrs.href;
                    this.model.target = attrs.target;

                    this.tippy = tippy($node.parentNode, {
                        content: this.$el,
                        showOnCreate: true,
                        trigger: 'manual',
                        allowHTML: true,
                        arrow: true,
                        interactive: true,
                        placement: 'top',
                        theme: 'fui-menu-bubble',
                        hideOnClick: true,
                        zIndex: 1000,
                        appendTo: () => { return document.body; },
                    });
                }
            }
        },

        destroyBubble() {
            if (this.tippy) {
                this.tippy.destroy();
                this.tippy = null;
            }
        },

        truncate(str, n) {
            return (str && str.length > n) ? `${str.substr(0, n - 1)}&hellip;` : str;
        },

        edit() {
            this.showEditModal = true;

            this.destroyBubble();

            this.editor.chain().blur().run();
        },

        closeModal() {
            this.showEditModal = false;
        },

        unlink() {
            this.editor.chain().focus().unsetLink().run();

            this.destroyBubble();
        },
    },

};

</script>

<style lang="scss">

.tippy-box[data-theme~="fui-menu-bubble"] {
    background-color: #1c2e36;
    border-radius: 3px;

    .tippy-arrow {
        z-index: 1;
        pointer-events: none;
    }

    .tippy-content {
        padding: 6px 12px 8px;
        box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.2);
    }

    a {
        font-size: 12px;
        color: #fff;
        text-decoration: none;
        display: inline-block;
        padding: 0 0 0 7px;

        &:hover {
            color: #ddd;
        }

        &:before {
            content: '';
            padding-left: 10px;
            border-left: 1px solid rgba(255,255,255,.3);
        }

        &:first-child {
            padding-left: 0;

            &:before {
                padding-left: 0;
                border-left: none;
            }
        }
    }
}

</style>
