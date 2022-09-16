<template>
    <div class="input input-wrap" :class="{ errors: false }">
        <editor-content class="fui-tags-list" :class="{ errors: false }" :editor="editor" />

        <div v-if="variables.length" class="select-list-container" :class="{ 'is-open': isOpen }">
            <div class="fui-field-add-variable-icon" @click.prevent="selectVariable">
                <svg aria-hidden="true" focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M384 240v32c0 6.6-5.4 12-12 12h-88v88c0 6.6-5.4 12-12 12h-32c-6.6 0-12-5.4-12-12v-88h-88c-6.6 0-12-5.4-12-12v-32c0-6.6 5.4-12 12-12h88v-88c0-6.6 5.4-12 12-12h32c6.6 0 12 5.4 12 12v88h88c6.6 0 12 5.4 12 12zm120 16c0 137-111 248-248 248S8 393 8 256 119 8 256 8s248 111 248 248zm-48 0c0-110.5-89.5-200-200-200S56 145.5 56 256s89.5 200 200 200 200-89.5 200-200z" /></svg>
            </div>

            <div class="fui-toolbar-dropdown-container fui-toolbar-dropdown-variables" style="display: none;">
                <variable-list :variables="variables" @updated="addVariable" />
            </div>
        </div>

        <div class="hidden">
            <br><input v-model="context._value" :name="context.node.name" class="input text fullwidth">
        </div>
    </div>
</template>

<script>
import { find } from 'lodash-es';

import tippy from 'tippy.js';
import 'tippy.js/themes/light-border.css';

import { Node } from '@tiptap/core';
import { Editor, EditorContent } from '@tiptap/vue-3';

// TipTap - Nodes
import Document from '@tiptap/extension-document';
import Paragraph from '@tiptap/extension-paragraph';
import Text from '@tiptap/extension-text';

// TipTap - Custom
import VariableTag from './richtext/variable-tag/VariableTag';
import VariableList from './richtext/variable-tag/VariableList.vue';

const OneLiner = Node.create({
    name: 'oneLiner',
    topNode: true,
    content: 'block',
});

export default {
    name: 'VariableTextInput',

    components: {
        EditorContent,
        VariableList,
    },

    props: {
        context: {
            type: Object,
            required: true,
        },
    },

    data() {
        return {
            tippy: null,
            isOpen: false,
            mounted: false,
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

        plainTextVariables() {
            return this.$store.getters['form/plainTextFields'](true);
        },

        generalVariables() {
            return this.$store.getters['form/generalFields'];
        },

        userVariables() {
            return this.$store.getters['form/userFields'];
        },

        numberVariables() {
            return this.$store.getters['form/numberFields']();
        },
    },

    watch: {
        jsonContent(newValue) {
            this.context.node.input(newValue);
        },
    },

    created() {
        // For the moment, we have to hard-code these variable lists in the component
        // Not overly flexibly, but well think of something a bit later...
        const variablesAttribute = this.context.attrs.variables || '';

        if (variablesAttribute && this[variablesAttribute]) {
            this.variables = this[variablesAttribute];
        }
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

            const $template = this.$el.querySelector('.fui-toolbar-dropdown-variables');
            const $button = this.$el.querySelector('.fui-field-add-variable-icon');

            if ($template && $button) {
                $template.style.display = 'block';
                const self = this;

                this.tippy = tippy($button, {
                    content: $template,
                    trigger: 'click',
                    allowHTML: true,
                    arrow: false,
                    interactive: true,
                    placement: 'bottom-end',
                    theme: 'light-border toolbar-dropdown',
                    zIndex: 1000,
                    hideOnClick: true,
                    offset: [0, 1],
                    onShow(instance) {
                        self.isOpen = true;
                    },
                    onHide(instance) {
                        self.isOpen = false;
                    },
                });
            }
        });
    },

    beforeUnmount() {
        if (this.editor) {
            this.editor.destroy();
        }
    },

    methods: {
        getExtensions() {
            const extensions = [
                OneLiner,
                Paragraph,
                Text,
                VariableTag.configure({ field: this }),
            ];

            return extensions;
        },

        valueToContent(value) {
            if (!value) {
                return '';
            }

            // Split the value into an array, like `['some text', '{var}', 'more text']` to make it easier to deal with
            // Then, replace `{var}` with `<variable-tag>{ label: 'Some label', value: 'some-value' }</variable-tag>`
            // The variable content is plucked from the `variables` prop. Join in all back and that's our content
            return value.split(/({.*?})/).map((param) => {
                if (param.includes('{')) {
                    const variable = find(this.variables, { value: param });

                    if (variable) {
                        return `<variable-tag>${JSON.stringify(variable)}</variable-tag>`;
                    }
                }

                return param;
            }).join('');
        },

        contentToValue(content) {
            if (!content) {
                return '';
            }

            let newContent = '';

            // Join the returned JSON object into a single string ready to save
            content.forEach((node) => {
                if (node.type === 'paragraph' && node.content) {
                    node.content.forEach((param) => {
                        if (param.type === 'text') {
                            newContent += param.text;
                        } if (param.type === 'variableTag') {
                            newContent += param.attrs.value;
                        }
                    });
                }
            });

            return newContent;
        },

        addVariable(e) {
            this.tippy.hide();

            this.editor.chain().focus().setVariableTag({
                label: e.target.getAttribute('data-label'),
                value: e.target.getAttribute('data-value'),
            }).run();
        },
    },
};

</script>

<style lang="scss" scoped>

.fui-toolbar-dropdown-variables {
    display: block;
    max-height: 200px;
    overflow-y: auto;
    padding: 5px 0;
}

.input-wrap {
    position: relative;
}

.fui-field-add-variable-icon {
    position: absolute;
    display: flex;
    align-items: center;
    height: calc(100% - 2px);
    top: 50%;
    right: 1px;
    padding: 8px 10px;
    opacity: 1;
    cursor: pointer;
    color: #1c2e36;
    user-select: none;
    transform: translateY(-50%);
    border-left: 1px solid transparent;
    border-top: 1px solid transparent;
    border-bottom: 1px solid transparent;
    border-radius: 0 3px 3px 0;
    background-clip: padding-box;
    background-color: transparent;
    z-index: 1;
    transition: all 0.2s ease;

    svg {
        width: 16px;
        height: 16px;
        display: block;
        color: #8d959b;
        transition: color 0.2s ease;
    }

    &:hover,
    .is-open & {
        background-color: #fff;
        border-left-color: #d7dfe7;
    }

    .is-open & {
        border-bottom-color: #fff;
        border-bottom-right-radius: 0;
    }

    &:hover svg,
    .is-open & svg {
        color: #1c2e36;
    }
}

</style>

<style lang="scss">
@import 'craftcms-sass/mixins';

.fui-tags-list {
    // Override tiptap
    .ProseMirror {
        outline: none;
        // word-wrap: normal;
        // white-space: pre;
        overflow: hidden;
        padding: 6px 45px 6px 9px;

        @include input-styles;

        [data-is-showing-errors="true"] & {
            border-color: $errorColor;
        }
    }

    .ProseMirror-focused {
        @include input-focused-styles;
    }
}

</style>
