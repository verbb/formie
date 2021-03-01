<template>
    <nav class="fui-pages-menu">
        <ul>
            <field-page-tab
                v-for="(page, index) in pages"
                :key="page.id"
                :ref="'tab-' + index"
                :page-index="index"
                :label="page.label"
                :active="value"
                :errors="page.errors"
                @selected="selectTab"
            />
        </ul>

        <button class="btn fui-tab-btn" role="button" type="button" @click.prevent="editPages"></button>

        <field-page-modal v-if="modalActive" :visible="modalVisible" @close="onModalClose" />
    </nav>
</template>

<script>
import { mapState } from 'vuex';

import FieldPageTab from './FieldPageTab.vue';
import FieldPageModal from './FieldPageModal.vue';

export default {
    name: 'FieldPageTabs',

    components: {
        FieldPageTab,
        FieldPageModal,
    },

    props: {
        value: {
            type: String,
            default: '',
        },
    },

    data() {
        return {
            modalActive: false,
            modalVisible: false,
            tabs: [],
        };
    },

    computed: {
        ...mapState({
            pages: state => state.form.pages,
        }),
    },

    created() {
        this.tabs = this.$children;

        // Check if there's no selected tab
        /*
        if (document.location.hash === '') {
            this.$nextTick().then(() => {
                this.selectTab();
            });
        }
        */
    },

    methods: {
        editPages() {
            this.modalActive = true;
            this.modalVisible = true;
        },

        onModalClose() {
            this.modalActive = false;
            this.modalVisible = false;
        },

        selectTab(hash) {
            /*
            let foundTab = false;

            if (hash) {
                this.tabs.forEach(tab => {
                    tab.isActive = (tab.hash === hash);

                    if (tab.isActive) {
                        foundTab = true;
                        this.showTabPane(tab);
                    }
                });
            }

            // Select the first tab
            if (!foundTab && this.tabs.length) {
                this.tabs[0].isActive = true;

                this.showTabPane(this.tabs[0]);
            }
            */

            this.$emit('input', hash);

            // Update history state
            if (typeof history !== 'undefined') {
                history.replaceState(undefined, undefined, hash);
            }

            // Fixes Redactor fixed toolbars on previously hidden panes
            Garnish.$doc.trigger('scroll');
        },
    },
};

</script>
