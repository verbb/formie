<template>
    <nav class="fui-pages-menu">
        <ul>
            <field-edit-tab
                v-for="(page, index) in pages"
                :key="index"
                :ref="'tab-' + index"
                :page-index="index"
                :label="page.label"
                :handle="page.handle"
                :has-error="hasError(page.handle)"
                @selected="selectTab"
            />
        </ul>
    </nav>
</template>

<script>
import FieldEditTab from '@components/FieldEditTab.vue';

export default {
    name: 'FieldEditTabs',

    components: {
        FieldEditTab,
    },

    emits: ['selected'],

    props: {
        pages: {
            type: Array,
            default: () => [],
        },

        tabsWithErrors: {
            type: Array,
            default: () => [],
        },
    },

    data() {
        return {
            tabs: [],
        };
    },

    created() {
        this.tabs = this.$children;
    },

    methods: {
        hasError(handle) {
            return this.tabsWithErrors.includes(handle);
        },

        selectTab(handle) {
            var foundTab = false;

            if (handle) {
                this.tabs.forEach(tab => {
                    tab.isActive = (tab.handle === handle);

                    if (tab.isActive) {
                        foundTab = true;
                    }

                    this.showTabPane(tab);
                });
            }

            // Fixes Redactor fixed toolbars on previously hidden panes
            Garnish.$doc.trigger('scroll');
        },

        showTabPane(tab) {
            if (tab.isActive) {
                this.$emit('selected', tab);
            }
        },
    },
};

</script>
