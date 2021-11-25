<template>
    <toolbar-modal
        v-model="proxyShow"
        attach="body"
        :esc-to-close="true"
        :focus-trap="true"
        :confirm-button="$attrs['confirm-button']"
        @confirm="confirmModal"
        @cancel="cancelModal"
    >
        <template #title>{{ $attrs['modal-title'] | t('formie') }}</template>

        <div id="url-field" class="field" :class="{ 'has-errors': errors.includes('url') }">
            <div class="heading">
                <label id="url-label" class="required" for="url">{{ 'URL' | t('formie') }}</label>
            </div>

            <div class="input ltr" :class="{ 'errors': errors.includes('url') }">
                <input
                    id="url"
                    v-model="value.url"
                    type="text"
                    class="text fullwidth"
                    autofocus=""
                    autocomplete="off"
                    required
                >
            </div>

            <ul v-if="errors.includes('url')" class="errors">
                <li>{{ 'URL cannot be blank.' | t('formie') }}</li>
            </ul>
        </div>

        <div id="text-field" class="field">
            <div class="heading">
                <label id="text-label" for="text">{{ 'Text' | t('formie') }}</label>
            </div>

            <div class="input ltr">
                <input
                    id="text"
                    v-model="value.text"
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
                    v-model="value.target"
                    type="checkbox"
                    class="checkbox"
                >
                <label :for="targetId">
                    {{ 'Open link in new tab' | t('formie') }}
                </label>
            </div>    
        </div>
    </toolbar-modal>
</template>

<script>
import { getMarkRange } from 'tiptap-utils';
import { TextSelection } from 'prosemirror-state';

// import { getMarkRange } from '@utils/tiptap/marks';
import ToolbarModal from './ToolbarModal.vue';

export default {
    name: 'LinkToolbarModal',

    components: {
        ToolbarModal,
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

        value: {
            type: Object,
            default: () => {
                return this.proxyValue;
            },
        },
    },

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
            return this.field.settings.elementSiteId;
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
            this.$emit('input', newValue);
        },
    },

    methods: {
        cancelModal() {
            this.proxyShow = false;
        },

        confirmModal() {
            this.errors = [];

            if (!this.value.url) {
                this.errors.push('url');

                return;
            }

            const data = {
                href: this.value.url, 
                target: this.value.target ? '_blank' : '',
            };

            // Save the cursor position so we can restore it afterwards
            const { state } = this.editor;
            const { tr } = state;
            const { selection } = tr;
            const cursorPos = selection.$cursor ? selection.$cursor.pos : selection.from;

            // From the focused link, (cursor or highlighted text) get the full mark position range.
            // We need this to properly update the text and attributes.
            let range = getMarkRange(state.doc.resolve(tr.selection.anchor), state.schema.marks.link);

            // Here, we can't find the range, probably because we're adding a new link on a text node.
            // That's much easier to deal with, as it'll always be the selected range
            if (!range) {
                range = { from: tr.selection.from, to: tr.selection.to };
            }

            if (this.value.text) {
                // Cast as string, just in case.
                const text = this.value.text.toString();
                    
                // Insert the new text, replacing the old range
                const transaction = tr.insertText(text, range.from, range.to);

                // Now the selection length has likely changed, get it again
                const $start = transaction.doc.resolve(range.from);
                const $end = transaction.doc.resolve(range.from + text.length);

                // And re-select it so our attribute-update actually works.
                transaction.setSelection(new TextSelection($start, $end));

                // Apply the transaction
                this.editor.view.dispatch(transaction);
            }

            // Restore the cursor once the mark updates have been done
            this.editor.commands.link(data);

            this.proxyShow = false;
        },
    },
};

</script>
