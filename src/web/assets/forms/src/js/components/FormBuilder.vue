<script>
import { mapState } from 'vuex';

import FieldPage from './FieldPage.vue';
import FieldPageTabs from './FieldPageTabs.vue';
import FieldPill from './FieldPill.vue';
import ExistingFieldModal from './ExistingFieldModal.vue';

// Touch support for drag/drop
import { polyfill } from 'mobile-drag-drop';
import { scrollBehaviourDragImageTranslateOverride } from 'mobile-drag-drop/scroll-behaviour';

polyfill({
    dragImageTranslateOverride: scrollBehaviourDragImageTranslateOverride,
    holdToDrag: 100,
});

// Required listeners for polyfill behaviour
window.addEventListener('dragenter', event => {
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
            existingFields: state => state.formie.existingFields,
            fieldGroups: state => state.fieldGroups,
            pages: state => state.form.pages,
            form: state => state.form,
        }),

        formHash() {
            return this.$store.getters['form/formHash'];
        },

        enabledFieldGroups() {
            return this.fieldGroups.filter((group) => {
                if (group.label !== 'Internal') {
                    return group;
                }
            });
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
