<template>
    <ul v-if="isOpen" tabindex="-1" role="listbox" class="fui-variable-list">
        <li
            v-for="(variable, index) in variables"
            :key="index"
            role="option"
            :class="{ 'fui-list-item-heading': variable.heading }"
            :data-value="variable.value"
            :data-label="variable.label"
            @click.prevent="addVariable"
        >
            {{ variable.label }}
        </li>
    </ul>
</template>

<script>

export default {
    name: 'VariableList',

    props: {
        isOpen: {
            type: Boolean,
            default: false,
        },

        variables: {
            type: Array,
            default: () => [],
        },
    },

    methods: {
        addVariable(e) {
            this.$emit('updated', e);
        },
    },
};

</script>

<style lang="scss" scoped>

.fui-variable-list {
    position: absolute;
    width: auto;
    top: 100%;
    left: auto;
    right: 0;
    max-height: 200px;
    overflow-y: auto;
    padding: 5px 0;
    z-index: 800;
    border-radius: 0 0 3px 3px;
    border: 1px solid rgba(96, 125, 159, 0.25);
    background-color: #fff;
    transition: all 0.3s ease;
    display: none;
    outline: none;
    box-shadow: none;

    .is-open & {
        display: block;
    }
}

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
