<template>
    <div>
        <button v-tooltip="{ content: title, theme: 'fui-editor-tooltip' }" class="btn fui-toolbar-btn" :class="{ 'active': active }" @click.prevent="() => {}" @mousedown="onMouseDown">
            <svg-icon :content="{ icon, svg }" />
        </button>

        <div class="fui-toolbar-dropdown-container fui-toolbar-dropdown-variables" style="display: none;">
            <variable-list :variables="variables" @updated="addVariable" />
        </div>
    </div>
</template>

<script>
import tippy from 'tippy.js';
import 'tippy.js/themes/light-border.css';

import { truncate } from 'lodash-es';

import SvgIcon from '../SvgIcon.vue';
import VariableList from './VariableList.vue';

export default {
    name: 'VariableTagItem',

    components: {
        SvgIcon,
        VariableList,
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

        icon: {
            type: String,
            default: null,
        },

        svg: {
            type: String,
            default: null,
        },

        title: {
            type: String,
            default: null,
        },

        isActive: {
            type: Function,
            default: () => {},
        },
    },

    data() {
        return {
            tippy: null,
            variables: this.field.variables,
        };
    },

    computed: {
        active() {
            return this.isActive && this.isActive();
        },
    },

    mounted() {
        this.$nextTick(() => {
            const $template = this.$el.querySelector('.fui-toolbar-dropdown-variables');
            const $button = this.$el;

            if ($template && $button) {
                $template.style.display = 'block';

                this.tippy = tippy($button, {
                    content: $template,
                    trigger: 'click',
                    allowHTML: true,
                    arrow: false,
                    interactive: true,
                    placement: 'bottom-start',
                    theme: 'light-border toolbar-dropdown',
                    zIndex: 1000,
                    hideOnClick: true,
                    offset: [0, 1],
                });
            }
        });
    },

    methods: {
        addVariable(e) {
            this.tippy.hide();

            this.editor.chain().focus().setVariableTag({
                label: e.target.getAttribute('data-label'),
                value: e.target.getAttribute('data-value'),
            }).run();
        },

        truncate(string, options) {
            return truncate(string, options);
        },

        onMouseDown(e) {
            e.preventDefault();
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

</style>
