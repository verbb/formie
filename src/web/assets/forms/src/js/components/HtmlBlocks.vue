<template>
    <editor-content :editor="editor" />
</template>

<script>
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
import Link from '@formkit-components/inputs/richtext/link/Link';

export default {
    name: 'HtmlBlocks',

    components: {
        EditorContent,
    },

    props: {
        content: {
            type: [String, Array],
            default: '',
        },
    },

    data() {
        return {
            editor: null,
        };
    },

    watch: {
        content(newValue) {
            this.editor.chain().setContent(this.valueToContent(newValue), true).run();
        },
    },

    mounted() {
        this.editor = new Editor({
            extensions: this.getExtensions(),
            content: this.valueToContent(this.content),
        });
    },

    beforeUnmount() {
        if (this.editor) {
            this.editor.destroy();
        }
    },

    methods: {
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
            ];

            return extensions;
        },
    },
};

</script>
