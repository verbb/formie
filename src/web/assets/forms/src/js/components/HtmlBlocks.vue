<template>
    <editor-content :editor="editor" />
</template>

<script>
import { Editor, EditorContent } from 'tiptap';
import { Blockquote, Heading, OrderedList, BulletList, ListItem, Bold, Italic, Strike, Underline, Table, TableHeader, TableCell, TableRow } from 'tiptap-extensions';
import Link from './formulate/richtext/Link';

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
            this.editor.setContent(this.valueToContent(newValue));
        },
    },

    mounted() {
        this.editor = new Editor({
            extensions: this.getExtensions(),
            content: this.valueToContent(this.content),
        });
    },

    beforeDestroy() {
        this.editor.destroy();
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
                }
            }

            return value.length ? { type: 'doc', content: value } : null;
        },

        getExtensions() {
            var extensions = [
                new Bold(),
                new Italic(),
                new Blockquote(),
                new Strike(),
                new Underline(),
                new Link({ vm: this }),
                new Blockquote(),
                new OrderedList(),
                new BulletList(),
                new ListItem(),
                new Heading({ levels: [1,2,3,4,5,6] }),
            ];

            return extensions;
        },
    },
};

</script>
