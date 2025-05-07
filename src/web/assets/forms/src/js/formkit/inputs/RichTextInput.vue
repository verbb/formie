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
import { mapState } from 'vuex';
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
import Table from '@tiptap/extension-table';
import TableCell from '@tiptap/extension-table-cell';
import TableHeader from '@tiptap/extension-table-header';
import TableRow from '@tiptap/extension-table-row';
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
        ...mapState({
            editingField: (state) => { return state.formie.editingField; },
        }),

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
            return this.$store.getters['form/allFieldOptions']({
                includeGeneral: true,
                excludedTypes: [
                    'verbb\\formie\\fields\\Section',
                    'verbb\\formie\\fields\\Summary',
                ],
            });
        },

        calculationsVariables() {
            let fields = this.$store.getters['form/plainTextFields']({
                includeGeneral: false,
                includedTypes: [
                    'verbb\\formie\\fields\\Calculations',
                    'verbb\\formie\\fields\\Checkboxes',
                ],
            });

            // Exclude _this_ field - all because Calculations fields can support their own.
            // Maybe refactor this into the getter in Formie 2?
            fields = fields.filter((field) => {
                if (this.editingField && this.editingField.field) {
                    if (field.value === `{field:${this.editingField.field.settings.handle}}`) {
                        return false;
                    }
                }

                return true;
            });

            return fields;
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

        disablePasteRules() {
            return get(this.context.attrs, 'disable-paste-rules', false);
        },

        disableInputRules() {
            return get(this.context.attrs, 'disable-input-rules', false);
        },
    },

    watch: {
        jsonContent(newValue) {
            this.context.node.input(newValue);
        },
    },

    mounted() {
        // Setup config for editor, from field config
        const options = {
            extensions: this.getExtensions(),
            content: this.valueToContent(this.clone(this.context._value)),
            autofocus: false,
            onUpdate: () => {
                this.json = this.editor.getJSON().content;
                this.html = this.editor.getHTML();
            },
        };

        if (this.disablePasteRules) {
            options.enablePasteRules = false;
        }

        if (this.disableInputRules) {
            options.enableInputRules = false;
        }

        this.editor = new Editor(options);

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

        getTableOptions() {
            let options = [
                'insert-table',
                'delete-table',
                'add-col-before',
                'add-col-after',
                'delete-col',
                'add-row-before',
                'add-row-after',
                'delete-row',
                'merge-cells',
                'split-cells',
                'toggle-header-column',
                'toggle-header-row',
                'toggle-header-cell',
            ];

            if (this.context.attrs.table && this.context.attrs.table.length) {
                options = this.context.attrs.table;
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
                Table.configure({
                    resizable: true,
                }),
                TableRow,
                TableHeader,
                TableCell,

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
    .ProseMirror > ol,
    .ProseMirror > .tableWrapper ul,
    .ProseMirror > .tableWrapper ol {
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

    .ProseMirror > ul,
    .ProseMirror > .tableWrapper ul {
        list-style-type: disc;

        ul {
            list-style-type: disc;
        }
    }

    .ProseMirror > blockquote,
    .ProseMirror > .tableWrapper blockquote {
        border-left: 5px solid #edf2fc;
        border-radius: 2px;
        color: #606266;
        margin: 10px 0;
        padding-left: 1em;
    }

    .ProseMirror > pre,
    .ProseMirror > .tableWrapper pre {
        background: #0d0d0d;
        color: #fff;
        font-family: JetBrainsMono,monospace;
        padding: .75rem 1rem;
        border-radius: .5rem;
    }

    .ProseMirror > p > a,
    .ProseMirror > .tableWrapper p > a {
        color: #3397ff;
        text-decoration: underline;
    }

    .h1, .h2, .h3, .h4, .h5, .h6, h1, h2, h3, h4, h5, h6 {
        text-transform: none;
        color: #212529;
        margin-top: 0;
        margin-bottom: 0.5rem !important;
        font-weight: 400;
        line-height: 1.2;
    }

    .h1, h1 {
        font-size: 2rem;
        letter-spacing: -0.02em;
    }

    .h2, h2 {
        font-size: 1.8rem;
    }

    .h3, h3 {
        font-size: 1.6rem;
    }

    .h4, h4 {
        font-size: 1.4rem;
    }

    .h5, h5 {
        font-size: 1.2rem;
    }

    .h6, h6 {
        font-size: 1rem;
    }
}

@for $i from 1 to 10 {
    .fui-rich-text-rows-#{$i} .ProseMirror {
        min-height: 1rem * $i;
    }
}

// Table styles
.fui-editor {
    .ProseMirror {
        .tableWrapper {
            padding: 1rem 0;
            overflow-x: auto;

            table {
                border-collapse: collapse;
                table-layout: fixed;
                width: 100%;
                margin: 0;
                overflow: hidden;

                td,
                th {
                    min-width: 1em;
                    border: 2px solid #ced4da;
                    padding: 3px 5px;
                    vertical-align: top;
                    box-sizing: border-box;
                    position: relative;

                    > * {
                        margin-bottom: 0;
                    }
                }

                th {
                    font-weight: bold;
                    text-align: left;
                    background-color: #f1f3f5;
                }

                .selectedCell:after {
                    z-index: 2;
                    position: absolute;
                    content: "";
                    left: 0; right: 0; top: 0; bottom: 0;
                    background: rgba(200, 200, 255, 0.4);
                    pointer-events: none;
                }

                .column-resize-handle {
                    position: absolute;
                    right: -2px;
                    top: 0;
                    bottom: -2px;
                    width: 4px;
                    background-color: #adf;
                    pointer-events: none;
                }

                p {
                    margin: 0;
                }
            }
        }
    }

    .resize-cursor {
        cursor: ew-resize;
        cursor: col-resize;
    }
}

</style>
