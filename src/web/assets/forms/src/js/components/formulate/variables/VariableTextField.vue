<template>
    <div class="input input-wrap" :class="{ errors: false }">
        <editor-content class="fui-tags-list" :class="{ errors: false }" :editor="editor" />

        <div v-if="variables.length" v-on-clickaway="clickAway" class="select-list-container" :class="{ 'is-open': isOpen }">
            <div class="fui-field-add-variable-icon" @click.prevent="selectVariable">
                <svg aria-hidden="true" focusable="false" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path fill="currentColor" d="M384 240v32c0 6.6-5.4 12-12 12h-88v88c0 6.6-5.4 12-12 12h-32c-6.6 0-12-5.4-12-12v-88h-88c-6.6 0-12-5.4-12-12v-32c0-6.6 5.4-12 12-12h88v-88c0-6.6 5.4-12 12-12h32c6.6 0 12 5.4 12 12v88h88c6.6 0 12 5.4 12 12zm120 16c0 137-111 248-248 248S8 393 8 256 119 8 256 8s248 111 248 248zm-48 0c0-110.5-89.5-200-200-200S56 145.5 56 256s89.5 200 200 200 200-89.5 200-200z" /></svg>
            </div>

            <variable-list :is-open="isOpen" :variables="variables" @updated="addVariable" />
        </div>

        <div class="hidden">
            <br><input v-model="context.model" class="input text fullwidth">
        </div>
    </div>
</template>

<script>
import find from 'lodash/find';
import { directive as onClickaway } from 'vue-clickaway';

import { Editor, EditorContent, Text, Doc } from 'tiptap';
import VariableTag from './VariableTag';

import VariableList from './VariableList.vue';

// Provide a custom doc to just allow text (no paragraph and variable tags)
class VariableInputDoc extends Doc {
    get schema() {
        return {
            content: '(text | variableTag)*',
        };
    }
}

export default {
    name: 'VariableTextField',

    components: {
        EditorContent,
        VariableList,
    },

    directives: {
        onClickaway,
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
            return this.$store.getters['form/plainTextFields'](true);
        },

        generalVariables() {
            return this.$store.getters['form/generalFields'];
        },

        userVariables() {
            return this.$store.getters['form/userFields'];
        },
    },

    watch: {
        json(newValue) {
            if (!this.mounted) {
                return;
            }

            this.context.model = this.contentToValue(newValue);
        },
    },

    created() {
        // For the moment, we have to hard-code these variable lists in the component
        // Not overly flexibly, but well think of something a bit later...
        var variablesAttribute = this.context.attributes.variables || '';

        if (variablesAttribute && this[variablesAttribute]) {
            this.variables = this[variablesAttribute];
        }
    },

    mounted() {
        // We use tiptap to handle the complexities of `contenteditable`, among a million other things.
        // Setup a custom tiptap editor to just allow text (no paragraph), and our custom variable tag component.
        this.editor = new Editor({
            extensions: [
                new VariableInputDoc(),
                new Text(),
                new VariableTag({ field: this }),
            ],
            content: this.valueToContent(clone(this.context.model)),
            useBuiltInExtensions: false,
            onUpdate: ({ getJSON, getHTML }) => {
                this.json = getJSON().content;
                this.html = getHTML();
            },
        });

        this.$nextTick(() => this.mounted = true);
    },

    beforeDestroy() {
        this.editor.destroy();
    },

    methods: {
        valueToContent(value) {
            if (!value) {
                return '';
            }

            // Split the value into an array, like `['some text', '{var}', 'more text']` to make it easier to deal with
            // Then, replace `{var}` with `<variable-tag>{ label: 'Some label', value: 'some-value' }</variable-tag>`
            // The variable content is plucked from the `variables` prop. Join in all back and that's our content
            return value.split(/({.*?})/).map(param => {
                if (param.includes('{')) {
                    var variable = find(this.variables, { value: param });

                    if (variable) {
                        return '<variable-tag>' + JSON.stringify(variable) + '</variable-tag>';
                    }
                }

                return param;
            }).join(' ');
        },

        contentToValue(content) {
            if (!content) {
                return '';
            }

            // Join the returned JSON object into a single string ready to save
            return content.map(param => {
                if (param.type === 'text') {
                    return param.text;
                } else if (param.type === 'variableTag') {
                    return param.attrs.value;
                }
            }).join('');
        },

        addVariable(e) {
            this.editor.commands.variableTag({
                label: e.target.getAttribute('data-label'),
                value: e.target.getAttribute('data-value'),
            });

            this.isOpen = false;
        },

        selectVariable(e) {
            this.isOpen = !this.isOpen;
        },

        clickAway() {
            this.isOpen = false;
        },
    },

};

</script>

<style lang="scss" scoped>

.input-wrap {
    position: relative;
}

.fui-field-add-variable-icon {
    position: absolute;
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
@import '~craftcms-sass/mixins';

.fui-tags-list {
    // Override tiptap
    .ProseMirror {
        outline: none;
        word-wrap: normal;
        white-space: pre;
        overflow: hidden;
        padding: 6px 9px;

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
