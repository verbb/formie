import { h } from 'vue';
import { cleanChildren } from '../utils/vnode';

const TabList = {
    name: 'TabList',
    render() {
        // Add magic indexes to each <Tab> component
        cleanChildren(this.$slots.default()).forEach((node, index) => {
            node.componentOptions.propsData.index = index;
        });

        return h(
            'div',
            {
                role: 'tablist',
                'aria-orientation': this.tabOrientation,
            },
            this.$slots.default()
        );
    },
    computed: {
        tabCount() {
            const tabs = cleanChildren(this.$slots.default());
            return tabs.length;
        },
        isOnLastTab() {
            return this.tabState.activeTab === this.tabCount;
        },
        isOnFirstTab() {
            return this.tabState.activeTab === 0;
        },
    },
    methods: {
        tablistSetActiveTab(updater, { force = false } = {}) {
            const activeTab = this.isManual
                ? this.tabState.focusedTab !== null
                    ? this.tabState.focusedTab
                    : 0
                : this.tabState.activeTab;

            const newActiveTab =
        typeof updater === 'number'
            ? updater
            : updater({
                currentIndex: activeTab,
                tabCount: this.tabCount,
                isOnLastTab: this.isOnLastTab,
                isOnFirstTab: this.isOnFirstTab,
            });

            // If the updater return value is false, then we shouldn't run the update
            if (newActiveTab === false) return;

            // DO IT 👊
            this.tabState.focusedTab = newActiveTab;
            if (force || !this.isManual) {
                this.tabState.activeTab = newActiveTab;
            }
        },
    },
    provide() {
        return {
            tablistSetActiveTab: this.tablistSetActiveTab,
        };
    },
    inject: ['tabState', 'tabOrientation', 'setActiveTab'],
};

export default TabList;
