<template>
    <menu-bar-modal
        v-model="proxyShow"
        attach="body"
        :esc-to-close="true"
        :focus-trap="true"
        :confirm-button="$attrs['confirm-button']"
        @confirm="confirmModal"
        @cancel="cancelModal"
    >
        <template #title>{{ t('formie', $attrs['modal-title']) }}</template>

        <!-- eslint-disable vue/no-mutating-props -->
        <div id="url-field" class="field" :class="{ 'has-errors': errors.includes('url') }">
            <div class="heading">
                <label id="url-label" class="required" for="url">{{ t('formie', 'URL') }}</label>
            </div>

            <div class="input ltr" :class="{ 'errors': errors.includes('url') }">
                <input
                    id="url"
                    v-model="modelValue.url"
                    type="text"
                    class="text fullwidth"
                    autofocus=""
                    autocomplete="off"
                    required
                >
            </div>

            <ul v-if="errors.includes('url')" class="errors">
                <li>{{ t('formie', 'URL cannot be blank.') }}</li>
            </ul>
        </div>

        <div id="text-field" class="field">
            <div class="heading">
                <label id="text-label" for="text">{{ t('formie', 'Text') }}</label>
            </div>

            <div class="input ltr">
                <input
                    id="text"
                    v-model="modelValue.text"
                    type="text"
                    class="text fullwidth"
                    autofocus=""
                    autocomplete="off"
                >
            </div>
        </div>

        <div id="target-field" class="checkboxfield field">
            <div class="input ltr">
                <input
                    :id="targetId"
                    v-model="modelValue.target"
                    type="checkbox"
                    class="checkbox"
                >
                <label :for="targetId">
                    {{ t('formie', 'Open link in new tab') }}
                </label>
            </div>
        </div>

        <!-- eslint-enable vue/no-mutating-props -->
    </menu-bar-modal>
</template>

<script>
import { TextSelection } from 'prosemirror-state';

import { getMarkRange } from '@utils/tiptap/marks';
import MenuBarModal from '../MenuBarModal.vue';

export default {
    name: 'LinkMenuModal',

    components: {
        MenuBarModal,
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

        show: {
            type: Boolean,
            default: false,
        },

        modelValue: {
            type: Object,
            default: () => {
                return this.proxyValue;
            },
        },
    },

    emits: ['update:modelValue', 'close'],

    data() {
        return {
            targetId: `target-${Craft.randomString(10)}`,
            proxyShow: false,
            proxyValue: {
                url: null,
                text: null,
                target: null,
            },
            errors: [],
        };
    },

    computed: {
        elementSiteId() {
            return this.field.elementSiteId;
        },
    },

    watch: {
        show(newValue) {
            this.proxyShow = newValue;
        },

        proxyShow(newValue) {
            if (newValue === false) {
                this.$emit('close');
            }
        },

        proxyValue(newValue) {
            this.$emit('update:modelValue', newValue);
        },
    },

    methods: {
        cancelModal() {
            this.proxyShow = false;
        },

        confirmModal() {
            this.errors = [];

            if (!this.modelValue.url) {
                this.errors.push('url');

                return;
            }

            const data = { href: this.modelValue.url, target: this.modelValue.target ? '_blank' : '' };

            // Save the cursor position so we can restore it afterwards
            const { selection } = this.editor.state.tr;
            const cursorPos = selection.$cursor ? selection.$cursor.pos : selection.from;

            // Update the text attributes. Text is a little tricky for the moment
            this.editor.chain().focus().command(({
                commands, tr, state, dispatch,
            }) => {
                // From the focused link, (cursor or highlighted text) get the full mark position range.
                // We need this to properly update the text and attributes.
                let range = getMarkRange(state.doc.resolve(tr.selection.anchor), state.schema.marks.link);

                // Here, we can't find the range, probably because we're adding a new link on a text node.
                // That's much easier to deal with, as it'll always be the selected range
                if (!range) {
                    range = { from: tr.selection.from, to: tr.selection.to };
                }

                if (this.modelValue.text) {
                    // Cast as string, just in case.
                    const text = this.modelValue.text.toString();

                    // Insert the new text, replacing the old range
                    tr.insertText(text, range.from, range.to);

                    // Now the selection length has likely changed, get it again
                    const $start = tr.doc.resolve(range.from);
                    const $end = tr.doc.resolve(range.from + text.length);

                    // And re-select it so our attribute-update actually works.
                    tr.setSelection(new TextSelection($start, $end));
                }
            }).setLink(data).command(({
                commands, tr, state, dispatch,
            }) => {
                // Restore the cursor once the mark updates have been done
                if (cursorPos) {
                    tr.setSelection(TextSelection.create(tr.doc, cursorPos));
                }
            }).run();

            this.proxyShow = false;
        },
    },
};

</script>
