<template>
    <div>
        <div v-if="editor" class="fui-rich-text" :class="['fui-rich-text-rows-' + rows, { 'has-focus': isFocused() }]">
            <menu-bar v-if="buttons.length" ref="toolbar" :buttons="buttons" :editor="editor" :field="this" />
            <editor-content class="fui-editor" :editor="editor" />
        </div>

        <div v-if="$isDebug" class="input text" style="margin-top: 20px;">{{ jsonContent }}</div>
        <input v-model="context._value" :name="context.node.name" type="hidden">
    </div>
</template>

<script>
import { find, get } from 'lodash-es';

import { Editor, EditorContent } from '@tiptap/vue-3';

// TipTap - Marks
import Bold from '@tiptap/extension-bold';
import Code from '@tiptap/extension-code';
import Highlight from '@tiptap/extension-highlight';
import Italic from '@tiptap/extension-italic';
import Strike from '@tiptap/extension-strike';
import Subscript from '@tiptap/extension-subscript';
import Superscript from '@tiptap/extension-superscript';
import Underline from '@tiptap/extension-underline';

// TipTap - Nodes
import Blockquote from '@tiptap/extension-blockquote';
import BulletList from '@tiptap/extension-bullet-list';
import CodeBlock from '@tiptap/extension-code-block';
import Document from '@tiptap/extension-document';
import HardBreak from '@tiptap/extension-hard-break';
import Heading from '@tiptap/extension-heading';
import HorizontalRule from '@tiptap/extension-horizontal-rule';
import ListItem from '@tiptap/extension-list-item';
import OrderedList from '@tiptap/extension-ordered-list';
import Paragraph from '@tiptap/extension-paragraph';
import Text from '@tiptap/extension-text';

// TipTap - Extensions
import Dropcursor from '@tiptap/extension-dropcursor';
import Focus from '@tiptap/extension-focus';
import Gapcursor from '@tiptap/extension-gapcursor';
import History from '@tiptap/extension-history';
import TextAlign from '@tiptap/extension-text-align';

// TipTap - Custom
import Link from './richtext/link/Link';
import VariableTag from './richtext/variable-tag/VariableTag';

import MenuBar from './richtext/MenuBar.vue';

export default {
    name: 'RichTextField',

    components: {
        EditorContent,
        MenuBar,
    },

    props: {
        context: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            mounted: false,
            buttons: ['bold', 'italic'],
            editor: null,
            json: null,
            html: null,
            variables: {},
        };
    },

    computed: {
        jsonContent() {
            return this.contentToValue(this.json);
        },

        emailVariables() {
            return this.$store.getters['form/emailFields']();
        },

        numberVariables() {
            return this.$store.getters['form/numberFields']();
        },

        plainTextVariables() {
            return this.$store.getters['form/allFields'](true);
        },

        calculationsVariables() {
            return this.$store.getters['form/plainTextFields'](false);
        },

        allowSource() {
            return get(this.context.attrs, 'allow-source', false);
        },

        rows() {
            return get(this.context.attrs, 'rows', 10);
        },

        linkOptions() {
            return get(this.context.attrs, 'linkOptions', []);
        },
    },

    watch: {
        jsonContent(newValue) {
            this.context.node.input(newValue);
        },
    },

    mounted() {
        // Setup config for editor, from field config
        this.editor = new Editor({
            extensions: this.getExtensions(),
            content: this.valueToContent(this.clone(this.context._value)),
            autofocus: false,
            onUpdate: () => {
                this.json = this.editor.getJSON().content;
                this.html = this.editor.getHTML();
            },
        });

        this.json = this.editor.getJSON().content;
        this.html = this.editor.getHTML();

        this.$nextTick(() => {
            this.mounted = true;
        });
    },

    created() {
        // Populate the buttons from config - allow an empty array to remove buttons
        if (this.context.attrs.buttons) {
            this.buttons = this.context.attrs.buttons;
        }

        // For the moment, we have to hard-code these variable lists in the component
        // Not overly flexibly, but well think of something a bit later...
        const variablesAttribute = this.context.attrs.variables || '';

        if (variablesAttribute && this[variablesAttribute]) {
            this.variables = this[variablesAttribute];
        }
    },

    beforeUnmount() {
        if (this.editor) {
            this.editor.destroy();
        }
    },

    methods: {
        getFormattingOptions() {
            let options = ['paragraph', 'code-block', 'blockquote', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

            if (this.context.attrs.formatting && this.context.attrs.formatting.length) {
                options = this.context.attrs.formatting;
            }

            return options;
        },

        getExtensions() {
            const extensions = [
                // Core Extensions
                Document,
                Dropcursor,
                Gapcursor,
                HardBreak,
                Paragraph,
                Text,
                Focus.configure({ className: 'has-focus', mode: 'deepest' }),

                // Optional Marks
                Bold,
                Code,
                Highlight,
                Italic,
                Strike,
                Subscript,
                Superscript,
                Underline,

                // Optional Nodes
                Blockquote,
                BulletList,
                CodeBlock,
                Heading.configure({ levels: [1, 2, 3, 4, 5, 6] }),
                HorizontalRule,
                ListItem,
                OrderedList,

                // Optional Extensions
                History,
                TextAlign.configure({
                    types: ['heading', 'paragraph'],
                    defaultAlignment: 'start',
                }),

                // Optional Custom
                Link.configure({ openOnClick: false }),
                VariableTag.configure({ field: this }),
            ];

            return extensions;
        },

        valueToContent(value) {
            if (!value) {
                return null;
            }

            // If already an array, easy.
            if (!Array.isArray(value)) {
                try {
                    value = JSON.parse(value);
                } catch (e) {
                    console.log(e);
                    console.log(value);
                }
            }

            return value.length ? { type: 'doc', content: value } : null;
        },

        contentToValue(content) {
            return JSON.stringify(content);
        },

        isFocused() {
            return this.editor.isFocused;
        },
    },
};

</script>

<style lang="scss">
@import 'craftcms-sass/mixins';

// ==========================================================================
// Editor
// ==========================================================================

.fui-rich-text {
    position: relative;
    border-radius: 3px;
    border: 1px solid rgba(96, 125, 159, 0.25);
    z-index: 2;

    &.has-focus {
        box-shadow: 0 0 0 1px #127fbf, 0 0 0 3px rgb(18 127 191 / 50%);
    }

    // Override tiptap
    .ProseMirror {
        outline: none;
        word-wrap: normal;
        padding: 16px;
        min-height: 10rem;
        background-color: #fbfcfe;
        background-clip: padding-box;

        [data-is-showing-errors="true"] & {
            border-color: $errorColor;
        }

        &:focus {
            box-shadow: none;
        }
    }
}

.fui-editor {
    &,
    & * {
        box-sizing: border-box;
    }

    .ProseMirror > ul,
    .ProseMirror > ol {
        padding-left: 0 !important;
        margin-left: 24px;

        ul, ol {
            padding-left: 0 !important;
            margin-left: 24px;
        }

        p {
            margin: 0;
        }
    }

    .ProseMirror > ul {
        list-style-type: disc;

        ul {
            list-style-type: disc;
        }
    }

    .ProseMirror > blockquote {
        border-left: 5px solid #edf2fc;
        border-radius: 2px;
        color: #606266;
        margin: 10px 0;
        padding-left: 1em;
    }

    .ProseMirror > pre {
        background: #0d0d0d;
        color: #fff;
        font-family: JetBrainsMono,monospace;
        padding: .75rem 1rem;
        border-radius: .5rem;
    }

    .ProseMirror > p > a {
        color: #3397ff;
        text-decoration: underline;
    }
}

@for $i from 1 to 10 {
    .fui-rich-text-rows-#{$i} .ProseMirror {
        min-height: 1rem * $i;
    }
}

</style>
