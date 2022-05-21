import { h } from 'vue';

const TabPanel = {
    name: 'TabPanel',
    props: {
        index: {
            type: Number,
            required: true,
        },
    },
    computed: {
        isActive() {
            return this.tabState.activeTab === this.index;
        },
    },
    watch: {
        isActive(isActive) {
            if (isActive) {
                this.setActivePanelRef(this.$el);
            }
        },
    },
    render() {
        return h(
            'div',
            {
                role: 'tabpanel',
                'aria-labeledby': `tabs--${this.tabState._id}--tab--${this.index}`,
                id: `tabs--${this.tabState._id}--panel--${this.index}`,

                // CHANGE: Remove this for now, causes weird focus on all children
                // tabindex: '-1',
                hidden: !this.isActive,
            },
            this.$slots.default()
        );
    },
    inject: ['tabState', 'setActivePanelRef'],
};

export default TabPanel;
