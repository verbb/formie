import { cleanChildren } from '../utils/vnode';

const TabPanels = {
    name: 'TabPanels',
    render(createElement) {
    // Add magic indexes to each <TabPanel> component
        cleanChildren(this.$slots.default).forEach((node, index) => {
            node.componentOptions.propsData.index = index;
        });

        return createElement('div', this.$slots.default);
    },
};

export default TabPanels;
