import { h } from 'vue';

const TabPanels = {
    name: 'TabPanels',
    render() {
        // Add magic indexes to each <TabPanel> component
        const panels = [];

        this.$slots.default().forEach((node, index) => {
            node.props.index = index;

            panels.push(node);
        });

        return h('div', panels);
    },
};

export default TabPanels;
