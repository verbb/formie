import { h } from 'vue';
import { useId, useCustomId } from '../utils/ids';
import { cleanChildren } from '../utils/vnode';

const Tabs = {
    name: 'Tabs',
    props: {
        id: null,
        defaultIndex: {
            type: Number,
            default: 0,
            required: false,
        },
        orientation: {
            type: String,
            default: 'horizontal',
            validator: (value) => ['horizontal', 'vertical'].includes(value),
        },
        tabActivationMode: {
            type: String,
            default: 'auto',
            validator: (value) => ['auto', 'manual'].includes(value),
        },
    },
    data() {
        return {
            tabState: {
                activeTab: this.defaultIndex,
                activePanelRef: null,
                focusedTab: null,
                _id: this.id ? useCustomId(this.id) : useId(),
            },
        };
    },
    computed: {
        isManual() {
            return this.tabActivationMode === 'manual';
        },
    },
    methods: {
        setActiveTab(newActiveTab, { force = false } = {}) {
            this.tabState.focusedTab = newActiveTab;
            if (force || !this.isManual) {
                this.tabState.activeTab = newActiveTab;
            }
        },
        setActivePanelRef(ref) {
            this.tabState.activePanelRef = ref;
        },
        focusActivePanel() {
            if (this.tabState.activePanelRef) {
                this.tabState.activePanelRef.focus();
            }
        },
        focusTab(tabIndex) {
            this.tabState.focusedTab = tabIndex;
        },
    },
    provide() {
        return {
            tabState: this.tabState,
            setActiveTab: this.setActiveTab,
            setActivePanelRef: this.setActivePanelRef,
            focusActivePanel: this.focusActivePanel,
            tabOrientation: this.orientation,
            tabActivationMode: this.tabActivationMode,
        };
    },
    render() {
        return h('div', this.$slots.default());
    },
};

export default Tabs;
