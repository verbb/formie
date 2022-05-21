<template>
    <node-view-wrapper
        as="span"
        :class="['fui-tag-wrap', { 'active': selected }]"
        contenteditable="false"
        data-drag-handle
    >
        <span
            class="fui-tag"
            :class="{ 'is-active': selected }"
            contenteditable="false"
        >
            {{ label }}
            <span class="fui-tag-delete" @click.prevent="destroy"></span>
        </span>
    </node-view-wrapper>
</template>

<script>
import { NodeViewWrapper } from '@tiptap/vue-3';

export default {
    name: 'VariableTag',

    components: {
        NodeViewWrapper,
    },

    props: {
        editor: {
            type: Object,
            default: () => {},
        },

        node: {
            type: Object,
            default: () => {},
        },

        decorations: {
            type: Array,
            default: () => { return []; },
        },

        selected: {
            type: Boolean,
            default: false,
        },

        extension: {
            type: Object,
            default: () => {},
        },

        getPos: {
            type: Function,
            default: () => {},
        },

        updateAttributes: {
            type: Function,
            default: () => {},
        },
    },

    computed: {
        label() {
            return this.node.attrs.label;
        },

        value() {
            return this.node.attrs.value;
        },
    },

    methods: {
        destroy() {
            const pos = this.getPos();
            const range = { from: pos, to: pos + (this.node.nodeSize - 1) };

            this.editor.chain().focus().deleteRange(range).run();
        },
    },
};

</script>

<style lang="scss" scoped>

.fui-tag-wrap {
    display: inline-flex;
    margin: 0 1px;
    cursor: default;
}

.fui-tag {
    position: relative;
    background-color: #5C6BC0;
    color: #fff;
    border-radius: 2px;
    display: inline-flex;
    padding: 0 5px;
    margin: 0;
    font-size: 12px;
    padding-right: 20px;
    white-space: nowrap;

    &.is-active {
        outline: none;
        box-shadow: 0 0 0 3px rgba(123, 140, 232, 0.5);
    }

    .fui-tag-delete {
        position: absolute;
        top: 50%;
        right: 3px;
        width: 12px;
        height: 12px;
        background-size: contain;
        transform: translateY(-50%);
        background-position: center center;
        background-repeat: no-repeat;
        cursor: pointer;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 320 512'%3E%3Cpath fill='%23fff' d='M193.94 256L296.5 153.44l21.15-21.15c3.12-3.12 3.12-8.19 0-11.31l-22.63-22.63c-3.12-3.12-8.19-3.12-11.31 0L160 222.06 36.29 98.34c-3.12-3.12-8.19-3.12-11.31 0L2.34 120.97c-3.12 3.12-3.12 8.19 0 11.31L126.06 256 2.34 379.71c-3.12 3.12-3.12 8.19 0 11.31l22.63 22.63c3.12 3.12 8.19 3.12 11.31 0L160 289.94 262.56 392.5l21.15 21.15c3.12 3.12 8.19 3.12 11.31 0l22.63-22.63c3.12-3.12 3.12-8.19 0-11.31L193.94 256z'/%3E%3C/svg%3E");
    }
}

</style>
