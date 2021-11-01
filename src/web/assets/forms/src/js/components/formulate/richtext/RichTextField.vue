<template>
    <div>
        <div class="fui-rich-text" :class="'fui-rows-' + rows">
            <editor-menu-bar :editor="editor">
                <div slot-scope="" class="fui-toolbar">
                    <component
                        :is="button.component || 'RichTextToolbarButton'"
                        v-for="button in availableButtons"
                        :key="button.name"
                        :button="button"
                        :active="false"
                        :field="_self"
                        :editor="editor"
                    />

                    <button v-if="allowSource" v-tooltip="$options.filters.t('Show HTML Source', 'formie')" class="btn fui-toolbar-btn has-tooltip" @click.prevent="showSource = !showSource">
                        <i class="fal fa-code"></i>
                    </button>
                </div>
            </editor-menu-bar>

            <editor-content v-show="!showSource" class="fui-editor" :class="{ errors: false }" :editor="editor" />

            <!-- <editor-source v-if="showSource" :html="html" /> -->
        </div>

        <div class="hidden">
            <br>

            <textarea v-model="context.model" class="input text fullwidth"></textarea>
        </div>
    </div>
</template>

<script>
import find from 'lodash/find';
import { Editor, EditorContent, EditorMenuBar, Extension } from 'tiptap';
import { Blockquote, Heading, OrderedList, BulletList, ListItem, Bold, Italic, Strike, Underline, Table, TableHeader, TableCell, TableRow, HardBreak } from 'tiptap-extensions';
import VariableTag from '../variables/VariableTag';
import Link from './Link';
import EditorSource from './EditorSource.vue';

import RichTextToolbarButton from './RichTextToolbarButton.vue';
import VariableTagToolbarButton from './VariableTagToolbarButton.vue';
import LinkToolbarButton from './LinkToolbarButton.vue';

export default {
    name: 'RichTextField',

    components: {
        EditorContent,
        EditorMenuBar,
        RichTextToolbarButton,
        VariableTagToolbarButton,
        LinkToolbarButton,
        EditorSource,
    },

    props: {
        context: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            isOpen: false,
            mounted: false,
            variables: {},
            showSource: false,
            availableButtons: [],
            editor: null,
            json: null,
            html: null,
        };
    },

    computed: {
        emailVariables() {
            return this.$store.getters['form/emailFields']();
        },

        plainTextVariables() {
            return this.$store.getters['form/allFields'](true);
        },

        calculationsVariables() {
            return this.$store.getters['form/plainTextFields'](false);
        },

        buttons() {
            return this.context.attributes.buttons || [];
        },

        allowSource() {
            return this.context.attributes['allow-source'] || false;
        },

        rows() {
            return this.context.attributes.rows || 10;
        },
    },

    watch: {
        json(newValue) {
            if (!this.mounted) {
                return;
            }

            this.context.model = this.contentToValue(newValue);
            // this.$emit('input', this.contentToValue(newValue));
        },
    },

    mounted() {
        this.initToolbarButtons();

        this.editor = new Editor({
            extensions: this.getExtensions(),
            content: this.valueToContent(clone(this.context.model)),
            onUpdate: ({ getJSON, getHTML }) => {
                this.json = getJSON().content;
                this.html = getHTML();
            },
            disableInputRules: ['italic'],
            disablePasteRules: ['italic'],
        });

        this.json = this.editor.getJSON().content;
        this.html = this.editor.getHTML();

        this.$nextTick(() => {
            this.mounted = true;

            // Trigger an update in case fields are listening to what TipTap generates
            this.context.model = this.contentToValue(this.json);
        });
    },

    created() {
        // For the moment, we have to hard-code these variable lists in the component
        // Not overly flexibly, but well think of something a bit later...
        var variablesAttribute = this.context.attributes.variables || '';

        if (variablesAttribute && this[variablesAttribute]) {
            this.variables = this[variablesAttribute];
        }
    },

    beforeDestroy() {
        this.editor.destroy();
    },

    methods: {
        getExtensions() {
            var extensions = [
                new HardBreak(),
            ];

            var { buttons } = this;

            if (buttons.includes('bold')) {
                extensions.push(new Bold());
            }

            if (buttons.includes('italic')) {
                extensions.push(new Italic());
            }

            if (buttons.includes('quote')) {
                extensions.push(new Blockquote());
            }

            if (buttons.includes('strikethrough')) {
                extensions.push(new Strike());
            }

            if (buttons.includes('underline')) {
                extensions.push(new Underline());
            }

            if (buttons.includes('link')) {
                extensions.push(new Link({ vm: this }));
            }

            if (buttons.includes('quote')) {
                extensions.push(new Blockquote());
            }

            if (buttons.includes('orderedlist') || buttons.includes('unorderedlist')) {
                if (buttons.includes('orderedlist')) {
                    extensions.push(new OrderedList());
                }

                if (buttons.includes('unorderedlist')) {
                    extensions.push(new BulletList());
                }

                extensions.push(new ListItem());
            }

            if (buttons.includes('table')) {
                extensions.push(
                    new Table({ resizable: true }),
                    new TableHeader(),
                    new TableCell(),
                    new TableRow(),
                );
            }

            if (buttons.includes('h1') || buttons.includes('h2') || buttons.includes('h3') || buttons.includes('h4') || buttons.includes('h5') || buttons.includes('h6')) {
                
                var levels = [];

                if (buttons.includes('h1')) {
                    levels.push(1);
                }

                if (buttons.includes('h2')) {
                    levels.push(2);
                }

                if (buttons.includes('h3')) {
                    levels.push(3);
                }

                if (buttons.includes('h4')) {
                    levels.push(4);
                }

                if (buttons.includes('h5')) {
                    levels.push(5);
                }

                if (buttons.includes('h6')) {
                    levels.push(6);
                }

                extensions.push(new Heading({ levels }));
            }

            if (buttons.includes('variableTag')) {
                extensions.push(new VariableTag({ field: this }));
            }

            return extensions;
        },

        // TODO: Add this somewhere global
        richTextButtons() {
            return [
                { name: 'h1', text: Craft.t('formie', 'Heading 1'), command: 'heading', args: { level: 1 }, html: '<i class="far fa-heading"><sup>1</sup></i>' },
                { name: 'h2', text: Craft.t('formie', 'Heading 2'), command: 'heading', args: { level: 2 }, html: '<i class="far fa-heading"><sup>2</sup></i>' },
                { name: 'h3', text: Craft.t('formie', 'Heading 3'), command: 'heading', args: { level: 3 }, html: '<i class="far fa-heading"><sup>3</sup></i>' },
                { name: 'h4', text: Craft.t('formie', 'Heading 4'), command: 'heading', args: { level: 4 }, html: '<i class="far fa-heading"><sup>4</sup></i>' },
                { name: 'h5', text: Craft.t('formie', 'Heading 5'), command: 'heading', args: { level: 5 }, html: '<i class="far fa-heading"><sup>5</sup></i>' },
                { name: 'h6', text: Craft.t('formie', 'Heading 6'), command: 'heading', args: { level: 6 }, html: '<i class="far fa-heading"><sup>6</sup></i>' },
                { name: 'bold', text: Craft.t('formie', 'Bold'), command: 'bold', icon: 'bold' },
                { name: 'italic', text: Craft.t('formie', 'Italic'), command: 'italic', icon: 'italic' },
                { name: 'underline', text: Craft.t('formie', 'Underline'), command: 'underline', icon: 'underline' },
                { name: 'strikethrough', text: Craft.t('formie', 'Strikethrough'), command: 'strike', icon: 'strikethrough' },
                { name: 'unorderedlist', text: Craft.t('formie', 'Unordered List'), command: 'bullet_list', icon: 'list-ul' },
                { name: 'orderedlist', text: Craft.t('formie', 'Ordered List'), command: 'ordered_list', icon: 'list-ol' },
                { name: 'quote', text: Craft.t('formie', 'Blockquote'), command: 'blockquote', icon: 'quote-right' },
                { name: 'link', text: Craft.t('formie', 'Link'), command: 'link', icon: 'link', component: 'LinkToolbarButton' },
                { name: 'table', text: Craft.t('formie', 'Table'), command: 'createTable', args: { rowsCount: 3, colsCount: 3, withHeaderRow: false }, icon: 'table' },
                { name: 'variableTag', text: Craft.t('formie', 'Variables'), command: 'variableTag', icon: 'plus-circle', component: 'VariableTagToolbarButton' },
            ];
        },

        initToolbarButtons() {
            var selectedButtons = ['bold', 'italic'];

            if (this.buttons.length) {
                selectedButtons = this.buttons;
            }

            let buttons = selectedButtons.map(button => {
                return find(this.richTextButtons(), { name: button }) || button;
            });

            // Remove any non-objects. This would happen if you configure a button name that doesn't exist.
            buttons = buttons.filter(button => typeof button != 'string');

            this.availableButtons = buttons;
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
    },

};

</script>

<style lang="scss" scoped>

.fui-toolbar {
    position: absolute;
    top: 1px;
    left: 1px;
    right: 1px;
    background: #fff;
    border-radius: 3px 3px 0 0;
    padding: 4px 8px;
    align-items: center;
    flex-wrap: wrap;
    display: flex;
    z-index: 1;
    border-bottom: 1px rgba(49, 49, 93, 0.15) solid;
    box-shadow: 0 2px 3px 0 rgba(49, 49, 93, 0.075);
}

</style>

<style lang="scss">
@import '~craftcms-sass/mixins';

.fui-rich-text {
    position: relative;

    // Override tiptap
    .ProseMirror {
        outline: none;
        word-wrap: normal;
        overflow: hidden;
        padding: 6px 9px;
        min-height: 10rem;
        padding-top: 47px;

        @include input-styles;

        [data-is-showing-errors="true"] & {
            border-color: $errorColor;
        }
    }

    .ProseMirror-focused {
        @include input-focused-styles;
    }
}

@for $i from 1 to 10 {
    .fui-rich-text.fui-rows-#{$i} .ProseMirror {
        min-height: 1rem * $i;
    }
}

</style>
