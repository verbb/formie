<template>
    <span class="fui-tag-wrap" :contenteditable="false">
        <span
            v-on-clickaway="clickAway"
            class="fui-tag"
            :class="{ 'is-active': selected || isActive }"
            :contenteditable="false"
            @click.prevent="selectTag"
        >
            {{ label }}
            <span class="fui-tag-delete" @click.prevent="destroy"></span>
        </span>
    </span>
</template>

<script>
import { directive as onClickaway } from 'vue-clickaway';

export default {
    name: 'VariableTag',

    directives: {
        onClickaway,
    },

    props: {
        node: {
            type: Object,
            default: () => {},
        },

        updateAttrs: {
            type: Function,
            default: () => {},
        },

        view: {
            type: Object,
            default: () => {},
        },

        options: {
            type: Object,
            default: () => {},
        },

        getPos: {
            type: Function,
            default: () => {},
        },

        selected: {
            type: Boolean,
            default: false,
        },
    },

    data() {
        return {
            isActive: false,
        };
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
            let { tr } = this.view.state;
            let pos = this.getPos();
            tr.delete(pos, pos + this.node.nodeSize);
            this.view.dispatch(tr);
        },

        selectTag() {
            this.isActive = true;
        },

        clickAway() {
            this.isActive = false;
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
