<template>
    <div class="fui-editor-toolbar">
        <component
            :is="item.component || 'MenuBarItem'"
            v-for="(item, index) in availableButtons"
            :key="index"
            v-bind="item"
            :editor="editor"
            :field="field"
        />
    </div>
</template>

<script>
import MenuBarItem from './MenuBarItem.vue';
import LinkMenuBarItem from './link/LinkMenuBarItem.vue';
import VariableTagMenuBarItem from './variable-tag/VariableTagMenuBarItem.vue';

export default {
    components: {
        MenuBarItem,
        LinkMenuBarItem,
        VariableTagMenuBarItem,
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

        buttons: {
            type: Array,
            default: () => { return []; },
        },
    },

    data() {
        return {
            allButtons: [
                {
                    name: 'bold',
                    svg: 'bold',
                    title: Craft.t('formie', 'Bold'),
                    action: () => { return this.editor.chain().focus().toggleBold().run(); },
                    isActive: () => { return this.editor.isActive('bold'); },
                },
                {
                    name: 'italic',
                    svg: 'italic',
                    title: Craft.t('formie', 'Italic'),
                    action: () => { return this.editor.chain().focus().toggleItalic().run(); },
                    isActive: () => { return this.editor.isActive('italic'); },
                },
                {
                    name: 'strikethrough',
                    svg: 'strikethrough',
                    title: Craft.t('formie', 'Strike'),
                    action: () => { return this.editor.chain().focus().toggleStrike().run(); },
                    isActive: () => { return this.editor.isActive('strike'); },
                },
                {
                    name: 'subscript',
                    svg: 'subscript',
                    title: Craft.t('formie', 'Subscript'),
                    action: () => { return this.editor.chain().focus().toggleSubscript().run(); },
                    isActive: () => { return this.editor.isActive('subscript'); },
                },
                {
                    name: 'superscript',
                    svg: 'superscript',
                    title: Craft.t('formie', 'Superscript'),
                    action: () => { return this.editor.chain().focus().toggleSuperscript().run(); },
                    isActive: () => { return this.editor.isActive('superscript'); },
                },
                {
                    name: 'underline',
                    svg: 'underline',
                    title: Craft.t('formie', 'Underline'),
                    action: () => { return this.editor.chain().focus().toggleUnderline().run(); },
                    isActive: () => { return this.editor.isActive('underline'); },
                },
                {
                    name: 'code',
                    svg: 'brackets-curly',
                    title: Craft.t('formie', 'Inline Code'),
                    action: () => { return this.editor.chain().focus().toggleCode().run(); },
                    isActive: () => { return this.editor.isActive('code'); },
                },
                {
                    name: 'highlight',
                    icon: 'highlighter',
                    title: Craft.t('formie', 'Highlight'),
                    action: () => { return this.editor.chain().focus().toggleHighlight().run(); },
                    isActive: () => { return this.editor.isActive('highlight'); },
                },
                {
                    name: 'paragraph',
                    icon: 'paragraph',
                    title: Craft.t('formie', 'Paragraph'),
                    action: () => { return this.editor.chain().focus().setParagraph().run(); },
                },
                {
                    name: 'unordered-list',
                    svg: 'list-ul',
                    title: Craft.t('formie', 'Bullet List'),
                    action: () => { return this.editor.chain().focus().toggleBulletList().run(); },
                    isActive: () => { return this.editor.isActive('bulletList'); },
                },
                {
                    name: 'ordered-list',
                    svg: 'list-ol',
                    title: Craft.t('formie', 'Ordered List'),
                    action: () => { return this.editor.chain().focus().toggleOrderedList().run(); },
                    isActive: () => { return this.editor.isActive('orderedList'); },
                },
                {
                    name: 'code-block',
                    svg: 'code',
                    title: Craft.t('formie', 'Code Block'),
                    action: () => { return this.editor.chain().focus().toggleCodeBlock().run(); },
                    isActive: () => { return this.editor.isActive('codeBlock'); },
                },
                {
                    name: 'quote',
                    icon: 'quote-right',
                    title: Craft.t('formie', 'Blockquote'),
                    action: () => { return this.editor.chain().focus().toggleBlockquote().run(); },
                    isActive: () => { return this.editor.isActive('blockquote'); },
                },
                {
                    name: 'hr',
                    svg: 'horizontal-rule',
                    title: Craft.t('formie', 'Horizontal Rule'),
                    action: () => { return this.editor.chain().focus().setHorizontalRule().run(); },
                },
                {
                    name: 'line-break',
                    svg: 'page-break',
                    title: Craft.t('formie', 'Line Break'),
                    action: () => { return this.editor.chain().focus().setHardBreak().run(); },
                },
                {
                    name: 'clear-format',
                    svg: 'remove-format',
                    title: Craft.t('formie', 'Clear Format'),
                    action: () => { return this.editor.chain().focus().clearNodes().unsetAllMarks().run(); },
                },
                {
                    name: 'undo',
                    svg: 'undo',
                    title: Craft.t('formie', 'Undo'),
                    action: () => { return this.editor.chain().focus().undo().run(); },
                },
                {
                    name: 'redo',
                    svg: 'redo',
                    title: Craft.t('formie', 'Redo'),
                    action: () => { return this.editor.chain().focus().redo().run(); },
                },
                {
                    name: 'align-left',
                    icon: 'align-left',
                    title: Craft.t('formie', 'Align Left'),
                    action: () => { return this.editor.chain().focus().setTextAlign('left').run(); },
                },
                {
                    name: 'align-center',
                    icon: 'align-center',
                    title: Craft.t('formie', 'Align Center'),
                    action: () => { return this.editor.chain().focus().setTextAlign('center').run(); },
                },
                {
                    name: 'align-right',
                    icon: 'align-right',
                    title: Craft.t('formie', 'Align Right'),
                    action: () => { return this.editor.chain().focus().setTextAlign('right').run(); },
                },
                {
                    name: 'align-justify',
                    icon: 'align-justify',
                    title: Craft.t('formie', 'Align Justify'),
                    action: () => { return this.editor.chain().focus().setTextAlign('justify').run(); },
                },
                {
                    name: 'formatting',
                    icon: 'paragraph',
                    title: Craft.t('formie', 'Formatting'),
                    options: [
                        {
                            name: 'paragraph',
                            title: Craft.t('formie', 'Paragraph'),
                        },
                        {
                            name: 'code-block',
                            title: Craft.t('formie', 'Code Block'),
                            action: () => { return this.editor.chain().focus().toggleCodeBlock().run(); },
                        },
                        {
                            name: 'blockquote',
                            title: Craft.t('formie', 'Blockquote'),
                            action: () => { return this.editor.chain().focus().toggleBlockquote().run(); },
                        },
                        {
                            name: 'h1',
                            title: Craft.t('formie', 'Heading 1'),
                            action: () => { return this.editor.chain().focus().toggleHeading({ level: 1 }).run(); },
                        },
                        {
                            name: 'h2',
                            title: Craft.t('formie', 'Heading 2'),
                            action: () => { return this.editor.chain().focus().toggleHeading({ level: 2 }).run(); },
                        },
                        {
                            name: 'h3',
                            title: Craft.t('formie', 'Heading 3'),
                            action: () => { return this.editor.chain().focus().toggleHeading({ level: 3 }).run(); },
                        },
                        {
                            name: 'h4',
                            title: Craft.t('formie', 'Heading 4'),
                            action: () => { return this.editor.chain().focus().toggleHeading({ level: 4 }).run(); },
                        },
                        {
                            name: 'h5',
                            title: Craft.t('formie', 'Heading 5'),
                            action: () => { return this.editor.chain().focus().toggleHeading({ level: 5 }).run(); },
                        },
                        {
                            name: 'h6',
                            title: Craft.t('formie', 'Heading 6'),
                            action: () => { return this.editor.chain().focus().toggleHeading({ level: 6 }).run(); },
                        },
                    ],
                },
                {
                    name: 'link',
                    svg: 'link',
                    title: Craft.t('formie', 'Link'),
                    component: 'LinkMenuBarItem',
                    isActive: () => { return this.editor.isActive('link'); },
                },
                {
                    name: 'variableTag',
                    svg: 'plusCircle',
                    title: Craft.t('formie', 'Variables'),
                    component: 'VariableTagMenuBarItem',
                    isActive: () => { return this.editor.isActive('variableTag'); },
                },
            ],
        };
    },

    computed: {
        availableButtons() {
            const buttons = [];

            this.buttons.forEach((buttonName) => {
                const button = this.allButtons.find((x) => { return x.name === buttonName; });

                if (button) {
                    // Handle special-cases and sub-options. Maybe move to other components?
                    if (button.name === 'formatting') {
                        button.options = this.getEnabledOptions(button, this.field.getFormattingOptions());
                    }

                    buttons.push(button);
                }
            });

            return buttons;
        },
    },

    methods: {
        getEnabledOptions(button, collection) {
            const options = [];

            collection.forEach((optionName) => {
                const option = button.options.find((x) => { return x.name === optionName; });

                if (option) {
                    options.push(option);
                }
            });

            return options;
        },
    },
};

</script>

<style lang="scss">

.fui-editor-toolbar {
    position: relative;
    background: #fff;
    border-radius: 3px 3px 0 0;
    padding: 4px 8px;
    align-items: center;
    flex-wrap: wrap;
    display: flex;
    z-index: 5;
    border-bottom: 1px rgba(49, 49, 93, 0.15) solid;
    box-shadow: 0 2px 3px 0 rgba(49, 49, 93, 0.075);
}

</style>
