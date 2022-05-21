import { h } from 'vue';

const Tab = {
    name: 'Tab',
    props: {
        index: {
            type: Number,
            required: true,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        activeClass: {
            type: String,
        },
        inactiveClass: {
            type: String,
        },
    },
    computed: {
        isActive() {
            return this.tabState.activeTab === this.index;
        },
        isFocused() {
            return this.tabState.focusedTab === this.index;
        },
    },
    methods: {
        handleKeydown(event) {
            const vertical = this.tabOrientation === 'vertical';
            const horizontal = this.tabOrientation === 'horizontal';

            if (
                (horizontal && event.key === 'ArrowRight') ||
        (vertical && event.key === 'ArrowDown')
            ) {
                event.preventDefault();

                this.tablistSetActiveTab(({ currentIndex, tabCount }) => {
                    return (currentIndex + 1) % tabCount;
                });
            }

            if (
                (horizontal && event.key === 'ArrowLeft') ||
        (vertical && event.key === 'ArrowUp')
            ) {
                event.preventDefault();

                this.tablistSetActiveTab(({ currentIndex, tabCount }) => {
                    return (currentIndex - 1 + tabCount) % tabCount;
                });
            }

            // If in horizontal mode, focus the active panel on ArrowDown, for screenreaders
            if (horizontal && event.key === 'ArrowDown') {
                event.preventDefault();

                this.focusActivePanel();
            }

            if (event.key === 'Home') {
                event.preventDefault();

                this.tablistSetActiveTab(0);
            }

            if (event.key === 'End') {
                event.preventDefault();

                this.tablistSetActiveTab(({ tabCount }) => tabCount - 1);
            }
        },
        handleClick(e) {
            // CHANGE
            e.preventDefault();

            this.tablistSetActiveTab(this.index, { force: true });
        },
    },
    watch: {
        isFocused(isFocused) {
            if (isFocused) {
                this.$el.focus();
            }
        },
    },
    render() {
        return h(
            'button',
            {
                role: 'tab',
                'aria-disabled': this.disabled ? 'true' : 'false',
                'aria-selected': this.isActive ? 'true' : 'false',
                'aria-controls': `tabs--${this.tabState._id}--panel--${this.index}`,
                id: `tabs--${this.tabState._id}--tab--${this.index}`,
                tabindex: this.isActive ? null : '-1',
                class: [this.isActive ? this.activeClass : this.inactiveClass],
                onClick: this.handleClick,
                onKeydown: this.handleKeydown,
            },
            this.$slots.default()
        );
    },
    inject: [
        'tabState',
        'tablistSetActiveTab',
        'focusActivePanel',
        'tabOrientation',
    ],
};

export default Tab;
