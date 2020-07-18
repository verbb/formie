// eslint-disable-next-line
import { Tabs, Tab, TabList, TabPanels, TabPanel } from '@accessible-tabs';

export default Vue => {
    Vue.component('Tabs', Tabs);
    Vue.component('Tab', Tab);
    Vue.component('TabList', TabList);
    Vue.component('TabPanels', TabPanels);
    Vue.component('TabPanel', TabPanel);
};
