<template>
    <nav class="fui-pages-menu">
        <ul>
            <field-page-tab
                v-for="(page, index) in pages"
                :key="page.id"
                :ref="'tab-' + index"
                :page-index="index"
                :label="page.label"
                :active="modelValue"
                :errors="page.errors"
                @selected="selectTab"
            />
        </ul>

        <button class="btn fui-tab-btn" role="button" type="button" @click.prevent="editPages"></button>

        <field-page-modal v-if="showModal" v-model:showModal="showModal" @closed="onModalClosed" />
    </nav>
</template>

<script>
import { mapState } from 'vuex';

import FieldPageTab from '@components/FieldPageTab.vue';
import FieldPageModal from '@components/FieldPageModal.vue';

export default {
    name: 'FieldPageTabs',

    components: {
        FieldPageTab,
        FieldPageModal,
    },

    props: {
        modelValue: {
            type: String,
            default: '',
        },
    },

    emits: ['update:modelValue'],

    data() {
        return {
            showModal: false,
            tabs: [],
        };
    },

    computed: {
        ...mapState({
            pages: (state) => { return state.form.pages; },
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
            this.showModal = true;
        },

        onModalClosed() {
            this.showModal = false;
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

            this.$emit('update:modelValue', hash);

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
