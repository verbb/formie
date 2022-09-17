<template>
    <ul tabindex="-1" role="listbox" class="fui-variable-list">
        <li
            v-for="(variable, index) in variables"
            :key="index"
            role="option"
            :class="{ 'fui-list-item-heading': variable.heading }"
            :data-value="variable.value"
            :data-label="truncate(variable.label, { length: 60 })"
            @click.prevent="addVariable"
        >
            {{ truncate(variable.label, { length: 60 }) }}
        </li>
    </ul>
</template>

<script>
import { truncate } from 'lodash-es';

export default {
    name: 'VariableList',

    props: {
        isOpen: {
            type: Boolean,
            default: false,
        },

        variables: {
            type: Array,
            default: () => { return []; },
        },
    },

    emits: ['updated'],

    methods: {
        truncate(string, options) {
            return truncate(string, options);
        },

        addVariable(e) {
            this.$emit('updated', e);
        },
    },
};

</script>

<style lang="scss" scoped>

.fui-variable-list li {
    cursor: pointer;
    padding: 5px 14px;
    color: #606d7b;
    font-weight: 500;
    cursor: pointer;

    &:hover {
        background-color: #edf3fa;
    }

    &:first-child {
        margin-top: 0 !important;
    }

    &.fui-list-item-heading {
        text-transform: uppercase;
        font-size: 10px;
        padding: 0 14px;
        margin-top: 5px;
        color: #aebdce;
        border-bottom: 1px #dfe5ea solid;
        user-select: none;
        pointer-events: none;
    }
}

</style>
