<template>
    <div class="fui-fields-pane">
        <div class="fui-fields-wrapper">
            <div class="fui-tabs fui-field-tabs fui-editor-tabs">
                <field-page-tabs v-model="activePage" />
            </div>

            <div class="fui-fields-inner-wrapper">
                <div class="fui-fields-scroll">
                    <div
                        v-for="(page, index) in pages"
                        :id="'tab-fields-page-' + page.id"
                        :key="page.id"
                        class="fui-tab-page"
                        :class="{ 'hidden': activePage != '#tab-fields-page-' + (index + 1) }"
                    >
                        <field-page ref="pages" :page-index="index" v-bind="page" />
                    </div>
                </div>
            </div>
        </div>

        <div class="fui-sidebar-wrapper">
            <div class="fui-sidebar-scroll">
                <div v-if="!isStencil">
                    <h6 class="sidebar-title">{{ t('formie', 'Existing fields') }}</h6>

                    <existing-field-modal />

                    <hr>
                </div>

                <div v-for="(group, index) in enabledFieldGroups" :key="index">
                    <h6 class="sidebar-title">{{ group.label }}</h6>

                    <div class="fui-row small-padding">
                        <div v-for="(field, i) in group.fields" :key="i" class="fui-col-6">
                            <field-pill :type="field.type" />
                        </div>
                    </div>

                    <hr v-if="index != Object.keys(enabledFieldGroups).length - 1">
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { mapState } from 'vuex';

import FieldPage from '@components/FieldPage.vue';
import FieldPageTabs from '@components/FieldPageTabs.vue';
import FieldPill from '@components/FieldPill.vue';
import ExistingFieldModal from '@components/ExistingFieldModal.vue';

// Touch support for drag/drop
import { polyfill } from 'mobile-drag-drop';
import { scrollBehaviourDragImageTranslateOverride } from 'mobile-drag-drop/scroll-behaviour';

polyfill({
    dragImageTranslateOverride: scrollBehaviourDragImageTranslateOverride,
    holdToDrag: 100,
});

// Required listeners for polyfill behaviour
window.addEventListener('dragenter', (event) => {
    event.preventDefault();
});

window.addEventListener('touchmove', () => {});

export default {
    name: 'FormBuilder',

    components: {
        FieldPage,
        FieldPageTabs,
        FieldPill,
        ExistingFieldModal,
    },

    data() {
        let activePage = location.hash;
        if (activePage.indexOf('#tab-fields-page') !== 0) {
            activePage = '#tab-fields-page-1';
        }

        return {
            activePage,
            savedFormHash: '',
        };
    },

    computed: {
        ...mapState({
            fieldGroups: (state) => { return state.fieldGroups; },
            pages: (state) => { return state.form.pages; },
            form: (state) => { return state.form; },
        }),

        formHash() {
            return this.$store.getters['form/formHash'];
        },

        enabledFieldGroups() {
            return this.fieldGroups.filter((group) => {
                return (group.label !== 'Internal') ? group : false;
            });
        },

        isStencil() {
            return this.$store.state.form.isStencil;
        },
    },

    created() {
        // Provide good UX if the form has changed
        window.addEventListener('beforeunload', this.checkForChanges);

        // Store the initial state as a hash to compare later
        this.savedFormHash = this.formHash;
    },

    methods: {
        checkForChanges(event) {
            if (this.savedFormHash !== this.formHash) {
                event.returnValue = Craft.t('formie', 'Are you sure you want to leave?');
            }
        },

        saveUpdatedHash() {
            this.savedFormHash = this.formHash;
        },
    },
};

</script>

<style lang="scss">

// Fix height scrolling weirdness from Craft
#main-content:not(.has-sidebar):not(.has-details) #content-container {
    min-height: auto !important;
}

</style>
