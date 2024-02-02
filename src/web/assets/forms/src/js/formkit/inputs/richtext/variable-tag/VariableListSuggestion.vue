<template>
    <div class="fui-variable-list-suggest">
        <template v-if="items.length">
            <button
                v-for="(item, index) in items"
                :key="index"
                class="suggest-item"
                :class="{ 'is-selected': index === selectedIndex }"
                @click.prevent="selectItem(index)"
            >
                {{ item.label }} <code class="suggest-item-handle">{{ item.value }}</code>
            </button>
        </template>

        <div v-else class="suggest-item is-empty">
            {{ t('formie', 'No result') }}
        </div>
    </div>
</template>

<script>
export default {
    props: {
        items: {
            type: Array,
            required: true,
        },

        command: {
            type: Function,
            required: true,
        },
    },

    data() {
        return {
            selectedIndex: 0,
        };
    },

    watch: {
        items() {
            this.selectedIndex = 0;
        },
    },

    methods: {
        onKeyDown({ event }) {
            if (event.key === 'ArrowUp') {
                this.upHandler();
                return true;
            }

            if (event.key === 'ArrowDown') {
                this.downHandler();
                return true;
            }

            if (event.key === 'Enter') {
                this.enterHandler();
                return true;
            }

            return false;
        },

        upHandler() {
            this.selectedIndex = ((this.selectedIndex + this.items.length) - 1) % this.items.length;
        },

        downHandler() {
            this.selectedIndex = (this.selectedIndex + 1) % this.items.length;
        },

        enterHandler() {
            this.selectItem(this.selectedIndex);
        },

        selectItem(index) {
            const item = this.items[index];

            if (item) {
                this.command(item);
            }
        },
    },
};

</script>

<style lang="scss" scoped>

.suggest-item {
    display: block;
    margin: 0;
    width: 100%;
    text-align: left;
    padding: 5px 10px;
    color: #606d7b;
    font-weight: 500;

    &.is-selected,
    &:hover {
        background-color: #edf3fa;
    }
}

.suggest-item-handle {
    font-size: 10px !important;
    opacity: 0.6;
}

</style>
