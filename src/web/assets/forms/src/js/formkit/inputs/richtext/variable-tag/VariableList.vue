<template>
    <div tabindex="-1" role="listbox" class="fui-variable-list">
        <component
            :is="variable.heading ? 'div' : 'button'"
            v-for="(variable, index) in variables"
            :key="index"
            role="option"
            :class="{ 'fui-list-item-heading': variable.heading, 'fui-list-item': !variable.heading, 'is-selected': index === selectedIndex }"
            :data-item="index"
            :data-value="variable.value"
            :data-label="truncate(variable.label, { length: 60 })"
            @click.prevent="selectItem(index)"
        >
            {{ truncate(variable.label, { length: 60 }) }}
        </component>
    </div>
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

    data() {
        return {
            selectedIndex: 1,
        };
    },

    watch: {
        isOpen(newValue) {
            if (newValue) {
                document.addEventListener('keydown', this.onKeyDown);
            } else {
                document.removeEventListener('keydown', this.onKeyDown);
            }
        },
    },

    methods: {
        truncate(string, options) {
            return truncate(string, options);
        },

        onKeyDown(event) {
            if (event.code === 'ArrowUp') {
                this.upHandler();
                return true;
            }

            if (event.code === 'ArrowDown') {
                this.downHandler();
                return true;
            }

            if (event.code === 'Enter') {
                this.enterHandler();
                return true;
            }

            return false;
        },

        upHandler() {
            let prevIndex = this.selectedIndex - 1;
            const prevItem = this.variables[prevIndex];

            if (prevItem && prevItem.heading) {
                prevIndex--;
            }

            if (prevIndex < 1) {
                prevIndex = this.variables.length - 1;
            }

            this.selectedIndex = prevIndex;

            this.updateScrolling();
        },

        downHandler() {
            let nextIndex = this.selectedIndex + 1;
            const nextItem = this.variables[nextIndex];

            if (nextItem && nextItem.heading) {
                nextIndex++;
            }

            if (nextIndex >= this.variables.length) {
                nextIndex = 1;
            }

            this.selectedIndex = nextIndex;

            this.updateScrolling();
        },

        enterHandler() {
            this.selectItem(this.selectedIndex);
        },

        updateScrolling() {
            const $el = this.$el.querySelector(`[data-item="${this.selectedIndex}"]`);

            if ($el) {
                $el.scrollIntoView({ block: 'nearest', inline: 'start' });
            }
        },

        selectItem(index) {
            const item = this.variables[index];

            if (item) {
                this.$emit('updated', item);
            }
        },
    },
};

</script>

<style lang="scss" scoped>

.fui-list-item {
    display: block;
    margin: 0;
    width: 100%;
    text-align: left;
    padding: 5px 14px;
    color: #606d7b;
    font-weight: 500;

    &.is-selected,
    &:hover {
        background-color: #edf3fa;
    }

    &:first-child {
        margin-top: 0 !important;
    }
}

.fui-list-item-heading {
    text-transform: uppercase;
    font-size: 10px;
    padding: 0 14px;
    margin-top: 5px;
    color: #aebdce;
    border-bottom: 1px #dfe5ea solid;
    user-select: none;
    pointer-events: none;

    &:first-child {
        margin-top: 0 !important;
    }
}

</style>
